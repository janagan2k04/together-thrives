<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Session;
use Services\SampleData;

/**
 * Platform-wide announcements. The `announcements` table is in the schema with
 * the same audience ENUM the Figma dropdown offers, so the form is built
 * against the real columns — only the insert is missing.
 */
class AnnouncementController extends Controller
{
    /** Matches the announcements.audience ENUM. */
    public const AUDIENCES = [
        'all'              => 'All Users',
        'community'        => 'Community Members',
        'organization'     => 'Organizations',
        'grama_niladhari'  => 'Grama Niladharis',
        'blood_bank'       => 'Blood Banks',
    ];

    public function index(): void
    {
        $this->view('admin.announcements.index', [
            'title'         => 'Announcements',
            'announcements' => SampleData::announcements(),
            'audiences'     => self::AUDIENCES,
        ]);
    }

    public function create(): void
    {
        $this->view('admin.announcements.form', [
            'title'        => 'New Announcement',
            'heading'      => 'New Announcement',
            'announcement' => null,
            'audiences'    => self::AUDIENCES,
            'action'       => '/admin/announcements',
            'method'       => 'POST',
        ]);
    }

    public function store(): void
    {
        $this->validateFields();
        Session::flash('info', 'Publishing announcements arrives with the announcements backend — nothing was saved.');
        $this->redirect('/admin/announcements');
    }

    public function edit(string $id): void
    {
        foreach (SampleData::announcements() as $a) {
            if ((string) $a['id'] === $id) {
                $this->view('admin.announcements.form', [
                    'title'        => 'Edit Announcement',
                    'heading'      => 'Edit Announcement',
                    'announcement' => $a,
                    'audiences'    => self::AUDIENCES,
                    'action'       => '/admin/announcements/' . $a['id'],
                    'method'       => 'PUT',
                ]);
                return;
            }
        }
        $this->abort(404, 'Announcement not found.');
    }

    public function update(string $id): void
    {
        $this->validateFields();
        Session::flash('info', 'Editing announcements arrives with the announcements backend — nothing was changed.');
        $this->redirect('/admin/announcements');
    }

    private function validateFields(): void
    {
        $this->validate([
            'title'    => 'required|max:160',
            'body'     => 'required|max:2000',
            'audience' => 'required|in:' . implode(',', array_keys(self::AUDIENCES)),
        ]);
    }
}
