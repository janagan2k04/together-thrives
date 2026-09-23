<?php
namespace Controllers;

use Core\Auth;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Session;
use Models\District;
use Models\Organization;
use Services\EmailVerificationService;
use Services\RegistrationService;

/**
 * Sign in, sign out, the account-type chooser and all four registration forms.
 */
class AuthController extends Controller
{
    private const COMMUNITY_BENEFITS = [
        ['users', 'green', 'Join Community Efforts',
         'Participate in cleanups, donation drives and awareness campaigns.'],
        ['droplet', 'red', 'Save Lives',
         'Support blood donation and disaster relief initiatives.'],
        ['gift', 'amber', 'Give & Receive',
         'Donate items or funds and help those who need them most.'],
        ['shield-check', 'blue', 'Safe & Verified',
         'A secure platform with verified users and organizations.'],
    ];

    private const ORG_BENEFITS = [
        ['users', 'green', 'Reach More People',
         'Connect with communities and donors who care.'],
        ['megaphone', 'red', 'Organize & Manage Easily',
         'Create campaigns, manage activities and track your impact in one place.'],
        ['shield-check', 'amber', 'Build Trust',
         'Get verified and build credibility within the community.'],
        ['bar-chart', 'blue', 'Measure Impact',
         'Track contributions and showcase the difference you make.'],
    ];

    private const GN_BENEFITS = [
        ['users', 'green', 'Serve Your Community',
         'Access essential tools and services to support your village and people.'],
        ['clipboard', 'red', 'Verified & Trusted',
         'Secure registration process to ensure authentic and trusted service.'],
        ['clock', 'amber', 'Work Efficiently',
         'Manage records, submit reports and coordinate easily in one place.'],
        ['bar-chart', 'blue', 'Better Decisions',
         'Use data and insights to plan and improve community well-being.'],
    ];

    private const BLOOD_BANK_BENEFITS = [
        ['droplet', 'red', 'Reach Eligible Donors',
         'Notify nearby donors who match the blood types you need.'],
        ['megaphone', 'amber', 'Run Campaigns Easily',
         'Create donation drives and keep donors updated in one place.'],
        ['shield-check', 'blue', 'Verified & Trusted',
         'Licensed banks are verified before campaigns go public.'],
        ['bar-chart', 'green', 'Track Impact',
         'See how many donations each campaign brought in.'],
    ];

    /**
     * The accounts seeded by database/seeds/seed.sql, listed on the sign-in page
     * so a marker can get into any role without being told the passwords.
     * Hard-coded rather than queried: the page must never read password material
     * out of `users`, and these plaintexts only exist in the seed file anyway.
     * Shown only when config `demo_accounts` is on. Keep in step with the seed.
     */
    private const DEMO_ACCOUNTS = [
        ['Platform Admin',              'Administrator',    'admin@togetherthrives.com', 'Password@123'],
        ['Keerthy Ganesh',              'Community Member', 'keerthy@test.com',          'Password@123'],
        ['Janindu Perera',              'Community Member', 'janindu@test.com',          'Password@123'],
        ['Hope Foundation Lanka',       'Organization',     'hope@test.com',             'Password@123'],
        ['Ajmal Riyaz',                 'Grama Niladhari',  'ajmal@test.com',            'Password@123'],
        ['National Blood Bank Colombo', 'Blood Bank',       'bank@test.com',             'Password@123'],
    ];

    private RegistrationService $registration;
    private EmailVerificationService $emailVerification;

    public function __construct()
    {
        $this->registration      = new RegistrationService();
        $this->emailVerification = new EmailVerificationService();
    }

    // ---------------------------------------------------------------- sign in

    public function showLogin(): void
    {
        // No asideBenefits here: the demo account list takes over the foot of the
        // brand panel. With DEMO_ACCOUNTS=false the panel is simply title + lede.
        $this->view('auth.login', [
            'title'        => 'Sign In',
            'asideTitle'   => 'Welcome back!',
            'asideLede'    => 'Sign in to your account and continue making a positive impact in your community.',
            'demoAccounts' => Config::get('demo_accounts') ? self::DEMO_ACCOUNTS : [],
        ], 'auth');
    }

    public function login(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($data['email'], $data['password'])) {
            // Auth::attempt flashes its own message for suspended and pending
            // accounts; anything else is a plain bad-credentials failure.
            Session::flashInput(['email' => $data['email']]);
            if (!Session::has('_flash')) {
                Session::flash('error', 'Those credentials do not match our records.');
            }
            $this->redirect('/login');
        }

        $this->redirect(Auth::homeFor());
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/');
    }

    // ----------------------------------------------------------- registration

    /** Standalone account-type chooser - the same four cards as the landing page. */
    public function chooseType(): void
    {
        $this->view('auth.choose-type', ['title' => 'Create an Account'], 'guest');
    }

    public function showCommunityForm(): void
    {
        $this->view('auth.register.community', [
            'title'         => 'Create your account',
            'asideTitle'    => 'Create an account',
            'asideAccent'   => 'and make an impact',
            'asideBenefits' => self::COMMUNITY_BENEFITS,
            'wideForm'      => true,
        ], 'auth');
    }

    public function registerCommunity(): void
    {
        $data = $this->validate([
            'full_name'        => 'required|max:120',
            'email'            => 'required|email|max:150|unique:users,email',
            'phone'            => 'required|phone_lk',
            'password'         => 'required|password',
            'password_confirm' => 'required|matches:password',
        ]);

        $this->withDocument(
            fn(array $doc) => $this->registration->registerCommunity($data, $doc),
            'Please upload your NIC so we can verify your identity.'
        );

        Session::flash('success', 'Your account is ready - please sign in.');
        $this->redirect('/login');
    }

    public function showOrgForm(): void
    {
        $this->view('auth.register.organization', [
            'title'         => 'Create your organization account',
            'asideTitle'    => 'Register your organization',
            'asideAccent'   => 'and create greater impact',
            'asideLede'     => 'Join hands with communities, volunteers and donors to build a stronger, safer world.',
            'asideBenefits' => self::ORG_BENEFITS,
            'orgTypes'      => Organization::TYPES,
            'wideForm'      => true,
        ], 'auth');
    }

    public function registerOrganization(): void
    {
        $data = $this->validate([
            'org_name'         => 'required|max:150',
            'org_type'         => 'required|in:non_profit,community_group,blood_bank',
            'reg_number'       => 'required|max:60',
            'email'            => 'required|email|max:150|unique:users,email',
            'website'          => 'max:180',
            'description'      => 'required|max:300',
            'password'         => 'required|password',
            'password_confirm' => 'required|matches:password',
        ]);

        $this->submitPending(
            fn(?array $doc) => $this->registration->registerOrganization($data, $doc),
            'Please upload a document proving your organization is registered.'
        );
    }

    public function showGnForm(): void
    {
        $this->view('auth.register.gn', [
            'title'         => 'Grama Niladhari Registration',
            'asideTitle'    => 'Register as a',
            'asideAccent'   => 'Grama Niladhari',
            'asideTail'     => 'and serve your community',
            'asideLede'     => 'Join hands to create a stronger, safer and more connected community.',
            'asideBenefits' => self::GN_BENEFITS,
            'districts'     => (new District())->listAll(),
            'wideForm'      => true,
        ], 'auth');
    }

    public function registerGn(): void
    {
        // Constrain the district to real rows so a tampered id fails validation
        // rather than reaching the database as a foreign key violation.
        $districtIds = array_column((new District())->listAll(), 'id');

        $data = $this->validate([
            'full_name'        => 'required|max:120',
            'email'            => 'required|email|max:150|unique:users,email',
            'phone'            => 'required|phone_lk',
            'nic'              => 'required|nic_lk',
            'district_id'      => 'required|integer|in:' . implode(',', $districtIds),
            'ds_division'      => 'max:120',
            'gn_division'      => 'required|max:120',
            'password'         => 'required|password',
            'password_confirm' => 'required|matches:password',
        ]);

        $this->submitPending(
            fn(?array $doc) => $this->registration->registerGramaNiladhari($data, $doc),
            'Please upload the government document that confirms your appointment.'
        );
    }

    public function showBloodBankForm(): void
    {
        $this->view('auth.register.blood-bank', [
            'title'         => 'Blood Bank Registration',
            'asideTitle'    => 'Register your blood bank',
            'asideAccent'   => 'and help save lives',
            'asideLede'     => 'Reach eligible donors near you and coordinate donation drives in one place.',
            'asideBenefits' => self::BLOOD_BANK_BENEFITS,
            'wideForm'      => true,
        ], 'auth');
    }

    public function registerBloodBank(): void
    {
        $data = $this->validate([
            'bank_name'        => 'required|max:150',
            'license_no'       => 'required|max:60',
            'hospital_name'    => 'max:150',
            'email'            => 'required|email|max:150|unique:users,email',
            'phone'            => 'required|phone_lk',
            'password'         => 'required|password',
            'password_confirm' => 'required|matches:password',
        ]);

        $this->submitPending(
            fn(?array $doc) => $this->registration->registerBloodBank($data, $doc),
            'Please upload your blood bank licence or an equivalent official document.'
        );
    }

    /**
     * Redeem a confirmation link. Reachable signed in or signed out, because the
     * link is usually opened from a mail client in a different browser.
     *
     * An invalid token is not an error page - the visitor did nothing wrong, and
     * a 404 here reads as if the whole site is broken.
     */
    public function verifyEmail(string $token): void
    {
        $user = $this->emailVerification->confirm($token);

        $this->view('auth.verify-email', [
            'title'         => $user ? 'Email Confirmed' : 'Link No Longer Valid',
            'confirmed'     => $user !== null,
            'email'         => $user['email'] ?? '',
            'awaitingAdmin' => $user !== null && $user['status'] === STATUS_PENDING,
        ], 'auth-centre');
    }

    public function submitted(): void
    {
        $this->view('auth.submitted', [
            'title'    => 'Registration Submitted',
            'hideLogo' => false,
            'wideCard' => true,
        ], 'auth-centre');
    }

    /** Register, then send the applicant to the awaiting-verification screen. */
    private function submitPending(callable $register, string $missingDocMessage): void
    {
        $this->withDocument($register, $missingDocMessage);
        $this->redirect('/registration-submitted');
    }

    /**
     * Every registration form requires one uploaded document. It is mandatory,
     * and FileUploader rejects the wrong type or size by throwing - turn that
     * into a form error rather than a 500 page. Returns normally on success.
     */
    private function withDocument(callable $register, string $missingDocMessage): void
    {
        $document = Request::file('document');

        if (!$document) {
            Session::flashInput(Request::all(), ['document' => [$missingDocMessage]]);
            Session::flash('error', 'Please correct the highlighted fields.');
            $this->back();
        }

        try {
            $register($document);
        } catch (\PDOException $e) {
            // PDOException extends RuntimeException, so it has to be caught first
            // or a database fault would be shown to the visitor as a form error.
            throw $e;
        } catch (\RuntimeException $e) {
            // FileUploader rejected the file - wrong type or too large.
            Session::flashInput(Request::all(), ['document' => [$e->getMessage()]]);
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }
}
