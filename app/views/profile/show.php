<?php
/**
 * "My Profile" from Comm_User-Homepage.svg.
 *
 * The frame draws Username, Date of Birth, Gender and a full postal address.
 * None of those have columns on `users`, and the group ruled in week 2 that
 * unmapped Figma fields are dropped rather than faked — the same ruling that
 * shortened the registration forms. The panels below are the columns that
 * exist, in the frame's order.
 */
require_once APP_PATH . '/views/partials/icons.php';

$roleLabels = [
    ROLE_COMMUNITY    => 'Community Member',
    ROLE_ORGANIZATION => 'Organization',
    ROLE_GN           => 'Grama Niladhari',
    ROLE_BLOOD_BANK   => 'Blood Bank',
    ROLE_ADMIN        => 'Administrator',
];
$statusLabels = [
    STATUS_ACTIVE    => ['Active',    'success'],
    STATUS_PENDING   => ['Pending',   'pending'],
    STATUS_SUSPENDED => ['Suspended', 'danger'],
    STATUS_REJECTED  => ['Rejected',  'danger'],
];
[$statusText, $statusTone] = $statusLabels[$user['status']] ?? ['Unknown', 'neutral'];

$groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
$locales = ['en' => 'English', 'si' => 'සිංහල (Sinhala)', 'ta' => 'தமிழ் (Tamil)'];
?>

<p class="crumbs">
  <a href="<?= url(Core\Auth::homeFor()) ?>">Home</a> <span>›</span>
  <span class="is-current">My Profile</span>
</p>

<div class="page-head">
  <div class="page-head__text">
    <h1>My Profile</h1>
    <p>View and edit your profile.</p>
  </div>
</div>

<section class="panel" style="margin-bottom:var(--s-5)">
  <div class="panel__body">
    <div class="profile-head">
      <span class="profile-head__photo"><?= e(mb_strtoupper(mb_substr($user['full_name'] ?? '?', 0, 1))) ?></span>

      <div class="profile-head__main">
        <div class="profile-head__name">
          <h2><?= e($user['full_name'] ?? '') ?></h2>
          <span class="badge badge--<?= e($statusTone) ?>"><?= e($statusText) ?></span>
        </div>
        <div class="profile-head__facts">
          <div><?= icon('mail') ?><?= e($user['email'] ?? '') ?></div>
          <div><?= icon('phone') ?><?= e($user['phone'] ?: 'No phone number on file') ?></div>
          <div><?= icon('calendar') ?>Joined on <?= format_date($user['created_at'] ?? null) ?></div>
        </div>
      </div>

      <div class="profile-head__side">
        <div>Account Type<strong><?= e($roleLabels[$user['role']] ?? $user['role']) ?></strong></div>
        <div>Member ID<strong>TT-USER-<?= e(str_pad((string) ($user['id'] ?? 0), 6, '0', STR_PAD_LEFT)) ?></strong></div>
        <div>Email confirmed<strong><?= $user['email_verified_at'] ? format_date($user['email_verified_at']) : 'Not confirmed' ?></strong></div>
      </div>
    </div>
  </div>
</section>

<form method="post" action="<?= url('/profile') ?>">
  <?= csrf_field() ?>

  <div class="split split--even" style="margin-bottom:var(--s-5)">
    <section class="panel">
      <div class="panel__head"><?= icon('user') ?><h2>Personal Information</h2></div>
      <div class="panel__body">
        <div class="field <?= has_error('full_name') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="full_name">Full Name</label>
          <input class="field__input" id="full_name" name="full_name"
                 value="<?= old('full_name', $user['full_name'] ?? '') ?>">
          <span class="field__error"><?= error_for('full_name') ?></span>
        </div>

        <div class="field">
          <label class="field__label" for="nic">NIC</label>
          <input class="field__input" id="nic" value="<?= e($user['nic'] ?? '') ?>" disabled>
          <span class="field__hint">Your NIC is fixed once an administrator has verified it.</span>
        </div>

        <div class="field-row">
          <div class="field">
            <label class="field__label" for="blood_group">Blood Group</label>
            <select class="field__select" id="blood_group" name="blood_group">
              <option value="">Not stated</option>
              <?php foreach ($groups as $g): ?>
                <option value="<?= e($g) ?>" <?= ($user['blood_group'] ?? '') === $g ? 'selected' : '' ?>><?= e($g) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label class="field__label" for="locale">Language</label>
            <select class="field__select" id="locale" name="locale">
              <?php foreach ($locales as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= ($user['locale'] ?? 'en') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </section>

    <section class="panel">
      <div class="panel__head"><?= icon('map-pin') ?><h2>Contact &amp; Location</h2></div>
      <div class="panel__body">
        <div class="field <?= has_error('email') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="email">Email Address</label>
          <input class="field__input" id="email" name="email" type="email"
                 value="<?= old('email', $user['email'] ?? '') ?>">
          <span class="field__error"><?= error_for('email') ?></span>
        </div>

        <div class="field <?= has_error('phone') ? 'field--invalid' : '' ?>">
          <label class="field__label" for="phone">Phone Number</label>
          <input class="field__input" id="phone" name="phone"
                 value="<?= old('phone', $user['phone'] ?? '') ?>" placeholder="07X XXX XXXX">
          <span class="field__error"><?= error_for('phone') ?></span>
        </div>

        <div class="field">
          <label class="field__label" for="city_id">City</label>
          <select class="field__select" id="city_id" name="city_id">
            <option value="">Select a city</option>
            <?php foreach ($cities as $c): ?>
              <option value="<?= e($c['id']) ?>" <?= (string) ($user['city_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?> — <?= e($c['district']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </section>
  </div>

  <?php if ($detail): ?>
    <section class="panel" style="margin-bottom:var(--s-5)">
      <div class="panel__head"><?= icon('building') ?><h2>Verified Details</h2>
        <small>Set at registration and confirmed by an administrator</small></div>
      <div class="panel__body">
        <dl class="kv">
          <?php foreach ($detail as $key => $value): ?>
            <?php if (in_array($key, ['id', 'user_id', 'document_path', 'logo_path', 'created_at', 'updated_at', 'deleted_at', 'verified_by'], true)) continue; ?>
            <div>
              <dt><?= e(ucwords(str_replace('_', ' ', $key))) ?></dt>
              <dd><?= $value === null || $value === '' ? '—' : e((string) $value) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
      </div>
    </section>
  <?php endif; ?>

  <div class="page-head__actions">
    <a class="btn btn--outline" href="<?= url(Core\Auth::homeFor()) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit">Save Changes</button>
  </div>
</form>

<p class="sample-note" style="margin-top:var(--s-5)">
  <?= icon('help-circle') ?>
  Saving arrives with the account backend. The form validates and bounces back — it does not
  write to your row yet.
</p>
