<?php
/**
 * Error view partial
 * Used by FormController and ApprovalController for 403/404 responses.
 * Expects: $errorCode (int), $errorTitle (string), $errorMessage (string)
 */
?>
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:50vh;text-align:center;gap:1rem">
    <i class="ti <?= $errorCode === 404 ? 'ti-file-off' : 'ti-lock' ?>"
       style="font-size:3rem;color:var(--border)"></i>
    <div>
        <div style="font-size:2rem;font-weight:700;color:var(--text-muted)"><?= $errorCode ?></div>
        <div style="font-size:1.1rem;font-weight:600;margin-bottom:.4rem"><?= htmlspecialchars($errorTitle) ?></div>
        <div style="color:var(--text-muted);font-size:14px"><?= htmlspecialchars($errorMessage) ?></div>
    </div>
    <a href="javascript:history.back()" class="btn btn-ghost btn-sm">← Go Back</a>
</div>