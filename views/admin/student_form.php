<h1 class="h4"><?= $student ? 'Edit student' : 'Add student' ?></h1>
<div class="card">
  <form method="post" action="<?= e(base_url('admin/students')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($student): ?><input type="hidden" name="id" value="<?= (int)$student['id'] ?>"><?php endif; ?>
    <?php $passport = $student ? student_passport_url($student) : null; ?>
    <div class="mb-3">
      <label class="form-label">Profile picture</label>
      <div class="d-flex align-items-center gap-3 mb-2">
        <?php if ($passport): ?>
          <img src="<?= e($passport) ?>" alt="Current profile picture" class="avatar avatar-lg">
        <?php elseif ($student): ?>
          <div class="avatar avatar-lg avatar-initials"><?= e(student_initials($student)) ?></div>
        <?php endif; ?>
        <div class="flex-grow-1">
          <input class="form-control" type="file" name="passport" accept="image/jpeg,image/png,image/webp">
          <div class="small muted mt-1">JPEG, PNG or WEBP. Max 3MB. Leave empty to keep the current photo.</div>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Matric number</label>
      <input class="form-control font-tabular" name="matric_no" required value="<?= e($student['matric_no'] ?? '') ?>">
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">First name</label>
        <input class="form-control" name="first_name" required value="<?= e($student['first_name'] ?? '') ?>">
      </div>
      <div>
        <label class="form-label">Last name</label>
        <input class="form-control" name="last_name" required value="<?= e($student['last_name'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Other name</label>
      <input class="form-control" name="other_name" value="<?= e($student['other_name'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select class="form-select" name="gender">
        <option <?= ($student['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
        <option <?= ($student['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Faculty</label>
      <select class="form-select" name="faculty_id">
        <?php foreach ($faculties as $f): ?>
          <option value="<?= (int)$f['id'] ?>" <?= ((int)($student['faculty_id'] ?? 0) === (int)$f['id']) ? 'selected' : '' ?>><?= e($f['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Department</label>
      <select class="form-select" name="department_id">
        <?php foreach ($departments as $d): ?>
          <option value="<?= (int)$d['id'] ?>" <?= ((int)($student['department_id'] ?? 0) === (int)$d['id']) ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="grid-2 mb-3">
      <div>
        <label class="form-label">Level</label>
        <select class="form-select" name="level">
          <?php foreach ([100,200,300,400,500] as $lv): ?>
            <option value="<?= $lv ?>" <?= ((int)($student['level'] ?? 100) === $lv) ? 'selected' : '' ?>><?= $lv ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="<?= e($student['phone'] ?? '') ?>">
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input class="form-control" type="email" name="email" value="<?= e($student['email'] ?? '') ?>">
    </div>
    <?php if ($student): ?>
    <div class="mb-3">
      <label class="form-label"><?= !empty($student['user_id']) ? 'Set student password' : 'Create login password' ?></label>
      <input class="form-control" type="password" name="password" minlength="8" autocomplete="new-password">
      <div class="small muted mt-1"><?= !empty($student['user_id']) ? 'Leave empty to keep the current password. Minimum 8 characters.' : 'Optional. If provided, a portal account is created with this password.' ?></div>
    </div>
    <?php endif; ?>
    <button class="btn btn-primary" type="submit">Save student</button>
  </form>
</div>
