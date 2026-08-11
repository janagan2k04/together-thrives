<?php
namespace Controllers\Admin;

use Core\Controller;

/**
 * Admin screens that are designed in admin.svg but not built yet.
 *
 * They render a short "planned" page instead of 404ing, so the sidebar matches
 * the design and every link goes somewhere. When you build one of these for
 * real, point its route at your own controller and delete the method here.
 */
class PlaceholderController extends Controller
{
    public function suspended(): void
    {
        $this->render('Suspended Users and Posts',
            'Manage suspended accounts and removed content across all modules.',
            'Content moderation — week 6.');
    }

    public function complaints(): void
    {
        $this->render('Complaints & Feedback',
            'Resolve reported issues, disputes and user feedback across the platform.',
            'Support and complaints — week 6.');
    }

    public function announcements(): void
    {
        $this->render('Announcements',
            'Publish platform-wide notices to users, organizations and Grama Niladharis.',
            'Announcements — week 6.');
    }

    public function dengue(): void
    {
        $this->render('Dengue Risk Alerts',
            'Report dengue-prone areas and see alerts raised by others.',
            'Dengue module — week 5.');
    }

    public function admins(): void
    {
        $this->render('Admin Management',
            'Manage administrator accounts, roles and permissions.',
            'Only the one super administrator exists for now, by group decision.');
    }

    public function logs(): void
    {
        $this->render('System Logs',
            'Audit trail of all administrator and system actions on the platform.',
            'Actions are already written to storage/logs/audit.log by Logger::audit().');
    }

    public function analytics(): void
    {
        $this->render('Analytics & Reports',
            'Platform-wide performance across all disaster response modules.',
            'Analytics — week 7, once the modules have data to report on.');
    }

    public function settings(): void
    {
        $this->render('Settings',
            'Configure platform-wide preferences, languages and integrations.',
            'Settings — week 7.');
    }

    private function render(string $heading, string $lede, string $note): void
    {
        $this->view('admin.placeholder', [
            'title'   => $heading,
            'heading' => $heading,
            'lede'    => $lede,
            'note'    => $note,
        ]);
    }
}
