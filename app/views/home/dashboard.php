<div class="page-header">
  <h1>Dashboard</h1>
</div>
<div class="card">
  <h2 class="card__title">Welcome back, <?= e($user['full_name'] ?? '') ?></h2>
  <p style="color:var(--muted)">
    Your role: <span class="badge badge--success"><?= e(ucwords(str_replace('_',' ', $user['role'] ?? ''))) ?></span>
  </p>
  <p style="margin-top:var(--s-3);color:var(--muted);font-size:var(--fs-sm)">
    Module dashboards are built in weeks 3–5. This page confirms authentication,
    session handling, role-based sidebars and layouts are all working.
  </p>
</div>
