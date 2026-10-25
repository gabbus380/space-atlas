<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AtlasEdit;
use App\Models\User;
use App\Support\OrganizationDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $orgs = $q === '' ? array_slice(OrganizationDirectory::all(), 0, 40) : OrganizationDirectory::search($q, 40);

        return view('admin.directory.index', ['orgs' => $orgs, 'q' => $q]);
    }

    public function edit(string $org): View
    {
        $record = OrganizationDirectory::find($org);
        abort_unless($record, 404);
        $people = User::query()
            ->where('organization_id', $record['id'])
            ->orderBy('name')
            ->get();

        return view('admin.directory.edit', ['org' => $record, 'people' => $people]);
    }

    public function update(Request $request, string $org): RedirectResponse
    {
        $record = OrganizationDirectory::find($org);
        abort_unless($record, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'desc' => ['nullable', 'string', 'max:5000'],
            'email_domains' => ['nullable', 'string', 'max:500'],
        ]);
        $domains = OrganizationDirectory::domains(['email_domains' => $data['email_domains'] ?? '']);
        AtlasEdit::query()->updateOrCreate(
            ['collection' => 'orgs', 'record_id' => $record['id']],
            [
                'payload' => [
                    'name' => $data['name'],
                    'desc' => $data['desc'] ?? '',
                    'email_domains' => $domains,
                ],
                'updated_by' => $request->user()->id,
            ],
        );

        return back()->with('status', $data['name'].' saved.');
    }
}
