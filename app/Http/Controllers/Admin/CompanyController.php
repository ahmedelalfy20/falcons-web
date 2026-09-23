<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Companies under our management (homepage logo strip). */
class CompanyController extends Controller
{
    public function __construct(private ImageOptimizer $images, private AuditLogger $audit) {}

    private function guard(): void
    {
        abort_unless(request()->user()->hasPermission(Permission::ContentManage), 403);
    }

    public function index()
    {
        $this->guard();

        return view('admin.content.companies.index', [
            'companies' => Company::orderBy('sort_order')->orderBy('id')->get(),
            'section' => (array) Setting::get('companies', []),
        ]);
    }

    public function create()
    {
        $this->guard();

        return view('admin.content.companies.form', ['company' => new Company(['is_active' => true, 'sort_order' => (int) Company::max('sort_order') + 1])]);
    }

    public function store(Request $request)
    {
        $this->guard();
        $data = $this->validated($request, true);
        $data['logo'] = $this->storeLogo($request);
        $company = Company::create($data);
        $this->audit->log('content.company_created', $company, ['name' => $company->name]);

        return redirect()->route('admin.companies.index')->with('success', __('Company ":name" added.', ['name' => $company->name]));
    }

    public function edit(Company $company)
    {
        $this->guard();

        return view('admin.content.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $this->guard();
        $data = $this->validated($request, false);
        if ($request->hasFile('logo')) {
            $old = $company->logo;
            $data['logo'] = $this->storeLogo($request);
        }
        $company->update($data);
        if (isset($old)) {
            $this->images->delete($old);
        }
        $this->audit->log('content.company_updated', $company, ['name' => $company->name]);

        return redirect()->route('admin.companies.index')->with('success', __('Company saved.'));
    }

    public function destroy(Company $company)
    {
        $this->guard();
        $this->images->delete($company->logo);
        $this->audit->log('content.company_deleted', $company, ['name' => $company->name]);
        $company->delete();

        return redirect()->route('admin.companies.index')->with('success', __('Company removed.'));
    }

    /** Section heading / subtitle / visibility. */
    public function section(Request $request)
    {
        $this->guard();
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:80'], 'title_ar' => ['nullable', 'string', 'max:80'],
            'subtitle' => ['nullable', 'string', 'max:200'], 'subtitle_ar' => ['nullable', 'string', 'max:200'],
            'speed' => ['required', 'in:slow,normal,fast'],
        ]);
        $data['enabled'] = $request->boolean('enabled');
        Setting::put('companies', $data);
        $this->audit->log('content.companies_section_updated', null, []);

        return back()->with('success', __('Section settings saved.'));
    }

    private function storeLogo(Request $request): string
    {
        try {
            // Keep transparency; no thumbnail needed for a logo.
            return $this->images->store($request->file('logo'), 'companies', 480, null, 90)['path'];
        } catch (\RuntimeException) {
            throw ValidationException::withMessages(['logo' => __('We could not read this image. Please upload a PNG, WebP or JPG logo.')]);
        }
    }

    private function validated(Request $request, bool $creating): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'name_ar' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'url:http,https', 'max:255'],
            'logo' => [$creating ? 'required' : 'nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:4096'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
        ], ['logo.required' => __('Please upload the company logo.')]);
        $v['is_active'] = $request->boolean('is_active');
        $v['on_light'] = $request->boolean('on_light');
        unset($v['logo']);

        return $v;
    }
}
