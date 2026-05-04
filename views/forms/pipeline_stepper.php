<?php

$statusOrder = [
    'draft'               => 'Draft',
    'submitted'           => 'Submitted',
    'supervisor_reviewed' => 'Supervisor',
    'department_checked'  => 'Dept. Check',
    'checker_approved'    => 'Checker',
    'final_approved'      => 'Final Approval',
    'completed'           => 'Approved',
];

$isRejected   = $form['status'] === 'rejected';
$form = 
$statusKeys   = array_keys($statusOrder);
$currentIndex = array_search($form['status'], $statusKeys, true);
if ($currentIndex === false) $currentIndex = 0;
$total        = count($statusOrder);
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%"
       style="border-collapse:collapse;margin:1.5rem 0;table-layout:fixed;">
  <tr>
    <?php foreach ($statusOrder as $statusKey => $label):
        $i = array_search($statusKey, $statusKeys, true);

        if ($isRejected) {
            $state = $i < $currentIndex ? 'done' : 'pending';
        } elseif ($i < $currentIndex) {
            $state = 'done';
        } elseif ($i === $currentIndex) {
            $state = 'active';
        } else {
            $state = 'pending';
        }

        // Previous step state 
        $leftColor = '#d1d5db';
        if ($i > 0) {
            if ($isRejected) {
                $ps = ($i - 1) < $currentIndex ? 'done' : 'pending';
            } elseif (($i - 1) < $currentIndex) {
                $ps = 'done';
            } elseif (($i - 1) === $currentIndex) {
                $ps = 'active';
            } else {
                $ps = 'pending';
            }
            $leftColor = ($ps === 'done') ? '#16a34a' : '#d1d5db';
        }

        $rightColor = ($state === 'done') ? '#16a34a' : '#d1d5db';

        // Circle colours
        $bg     = $state === 'done'   ? '#16a34a' : '#ffffff';
        $border = $state === 'done'   ? '#16a34a'
                : ($state === 'active' ? '#2563eb' : '#d1d5db');
        $color  = $state === 'done'   ? '#ffffff'
                : ($state === 'active' ? '#2563eb' : '#9ca3af');

        // Label
        $lColor  = $state === 'done'   ? '#15803d'
                 : ($state === 'active' ? '#1d4ed8' : '#6b7280');
        $lWeight = $state === 'active' ? 'bold' : 'normal';
    ?>
    <td align="center" valign="top"
        style="padding:0;vertical-align:top;text-align:center;">

      <!-- Row: left-line | circle | right-line -->
      <table border="0" cellpadding="0" cellspacing="0" width="100%"
             style="border-collapse:collapse;">
        <tr>

          <!-- Left line -->
          <td width="50%" style="padding:0;vertical-align:middle;">
            <?php if ($i > 0): ?>
              <div style="height:2px;background:<?= $leftColor ?>;
                          font-size:0;line-height:0;overflow:hidden;">
              </div>
            <?php endif; ?>
          </td>

          <!-- Circle attribute of steps-->
          <td width="34" style="padding:0;vertical-align:middle;text-align:center;">
            <div style="width:34px;height:34px;border-radius:50%;
                        background:<?= $bg ?>;
                        border:2px solid <?= $border ?>;
                        color:<?= $color ?>;
                        font-size:13px;font-weight:bold;
                        font-family:Arial,sans-serif;
                        line-height:34px;text-align:center;
                        box-sizing:border-box;margin:0 auto;">
              <?= $state === 'done' ? '&#10003;' : ($i + 1) ?>
            </div>
          </td>

          <!-- Right line -->
          <td width="50%" style="padding:0;vertical-align:middle;">
            <?php if ($i < $total - 1): ?>
              <div style="height:2px;background:<?= $rightColor ?>;
                          font-size:0;line-height:0;overflow:hidden;">
              </div>
            <?php endif; ?>
          </td>

        </tr>
      </table>

      <!-- Label -->
      <div style="margin-top:6px;font-size:12px;font-family:Arial,sans-serif;
                  color:<?= $lColor ?>;font-weight:<?= $lWeight ?>;
                  text-align:center;white-space:nowrap;">
        <?= htmlspecialchars($label) ?>
      </div>

    </td>
    <?php endforeach; ?>
  </tr>
</table>

<?php if ($isRejected): ?>
  <p style="color:#b91c1c;font-family:Arial,sans-serif;font-size:13px;margin-top:4px;">
    &#10005; This form was <strong>rejected</strong>.
  </p>
<?php endif; ?>