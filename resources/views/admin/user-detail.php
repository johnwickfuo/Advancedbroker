<?php
$walletCurrency=(string)($wallet['currency_code']??$userCountry['currency_code']??'USD');
$walletScale=in_array($walletCurrency,['JPY','KRW'],true)?0:2;
$formatWallet=static fn(int $minor): string =>
    (new \App\Support\Money($minor,$walletCurrency))->format($walletCurrency.' ',$walletScale);
$available=(int)($wallet['available_balance_minor']??0);
$reserved=(int)($wallet['reserved_balance_minor']??0);
?>
<p class="eyebrow">User account</p>
<h1><?= e($user['first_name'].' '.$user['last_name']) ?></h1>

<div class="grid two dashboard-split">
  <section class="card">
    <h2>Account details</h2>
    <dl class="detail-list">
      <dt>Reference</dt><dd><?= e($user['uuid']) ?></dd>
      <dt>Email</dt><dd><?= e($user['email']) ?> · <?= !empty($user['email_verified_at'])?'verified':'unverified' ?></dd>
      <dt>Registered</dt><dd><?= e($user['created_at']) ?></dd>
      <dt>Last login</dt><dd><?= e($user['last_login_at']??'Never') ?></dd>
      <dt>2FA</dt><dd><?= !empty($user['two_factor_enabled_at'])?'Enabled':'Not enabled' ?></dd>
    </dl>
  </section>

  <section class="card">
    <h2>Change investment region</h2>
    <p class="muted">This changes the account’s assigned market and overrides future GeoIP resolution.</p>
    <form method="post" action="<?= e(route('admin.users.country',['user'=>$user['id']])) ?>" class="form-stack" data-confirm="Confirm the investment region change?">
      <?= app('csrf')->input() ?>
      <label class="field">Market
        <select name="country_id">
          <?php foreach($countries as $country): ?>
            <option value="<?= e($country['id']) ?>"<?= (int)($user['assigned_country_id']??$user['country_id'])===(int)$country['id']?' selected':'' ?>>
              <?= e($country['name']) ?> · <?= e($country['currency_code']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">Reason <span class="muted">(optional)</span><textarea name="reason" rows="2"></textarea></label>
      <button class="button" type="submit">Confirm market</button>
    </form>
  </section>
</div>

<section class="card">
  <p class="section-label">Wallet control</p>
  <h2>Credit or debit user</h2>
  <p class="muted">Every adjustment creates an immutable ledger transaction and an admin audit record. Debits cannot exceed the user's available balance.</p>

  <div class="metric-grid">
    <article class="metric-card"><span>Available balance</span><strong><?= e($formatWallet($available)) ?></strong></article>
    <article class="metric-card"><span>Reserved balance</span><strong><?= e($formatWallet($reserved)) ?></strong></article>
    <article class="metric-card"><span>Wallet currency</span><strong><?= e($walletCurrency) ?></strong></article>
  </div>

  <form method="post" action="<?= e(route('admin.users.wallet-adjustment',['user'=>$user['id']])) ?>" class="form-grid" data-confirm="Confirm this wallet adjustment? This action will be recorded permanently in the ledger.">
    <?= app('csrf')->input() ?>
    <label class="field">Action
      <select name="direction" required>
        <option value="CREDIT">Credit user</option>
        <option value="DEBIT">Debit user</option>
      </select>
    </label>
    <label class="field">Amount (<?= e($walletCurrency) ?>)
      <input name="amount" inputmode="<?= $walletScale===0?'numeric':'decimal' ?>" min="<?= $walletScale===0?'1':'0.01' ?>" step="<?= $walletScale===0?'1':'0.01' ?>" required placeholder="<?= $walletScale===0?'1000':'100.00' ?>">
    </label>
    <label class="field" style="grid-column:1/-1">Reason
      <textarea name="reason" rows="3" maxlength="500" required placeholder="Why is this manual balance adjustment being made?"></textarea>
    </label>
    <div style="grid-column:1/-1">
      <button class="button" type="submit">Record wallet adjustment</button>
    </div>
  </form>

  <?php if(!empty($recentLedger)): ?>
    <div class="compact" style="margin-top:28px">
      <h3>Recent wallet transactions</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Reference</th><th>Type</th><th>Direction</th><th>Amount</th><th>Balance after</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach($recentLedger as $entry):
            $entryCurrency=(string)$entry['currency_code'];
            $entryScale=in_array($entryCurrency,['JPY','KRW'],true)?0:2;
            $entryAmount=(new \App\Support\Money((int)$entry['amount_minor'],$entryCurrency))->format($entryCurrency.' ',$entryScale);
            $entryAfter=(new \App\Support\Money((int)$entry['balance_after_minor'],$entryCurrency))->format($entryCurrency.' ',$entryScale);
          ?>
            <tr>
              <td><?= e($entry['reference']) ?></td>
              <td><?= e($entry['transaction_type']) ?></td>
              <td><?= e($entry['direction']) ?></td>
              <td><?= e($entryAmount) ?></td>
              <td><?= e($entryAfter) ?></td>
              <td><?= e($entry['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Account status</h2>
  <form method="post" action="<?= e(route('admin.users.status',['user'=>$user['id']])) ?>" class="form-stack" data-confirm="Confirm this account status change?">
    <?= app('csrf')->input() ?>
    <label class="field">Status
      <select name="account_status">
        <option value="active"<?= $user['account_status']==='active'?' selected':'' ?>>Active</option>
        <option value="restricted"<?= $user['account_status']==='restricted'?' selected':'' ?>>Restricted</option>
        <option value="suspended"<?= $user['account_status']==='suspended'?' selected':'' ?>>Suspended</option>
      </select>
    </label>
    <label class="field">Reason <textarea name="reason" rows="2"><?= e($user['account_restriction_reason']??'') ?></textarea></label>
    <button class="button button-danger" type="submit">Update status</button>
  </form>
</section>

<section class="card">
  <p class="section-label">Popup message</p>
  <h2>Send notification</h2>
  <p class="muted">This appears as a popup the next time the user loads a page.</p>
  <form method="post" action="<?= e(route('admin.users.popup',['user'=>$user['id']])) ?>" class="form-stack">
    <?= app('csrf')->input() ?>
    <label class="field">Title<input name="title" maxlength="190" required placeholder="Account update"></label>
    <label class="field">Message<textarea name="body" rows="4" required></textarea></label>
    <label class="field">Show this popup how many times?<input type="number" name="repeat_count" min="1" max="100" value="1" required><small>It will appear once per page load or refresh until this number is reached.</small></label>
    <button class="button">Send popup</button>
  </form>

  <?php if(!empty($activePopups)): ?>
    <div class="compact" style="margin-top:24px">
      <h3>Active popup notifications</h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Title</th><th>Progress</th><th>Created</th><th></th></tr></thead>
          <tbody>
          <?php foreach($activePopups as $popup): ?>
            <tr>
              <td><?= e($popup['title']) ?></td>
              <td><?= e($popup['display_count']) ?> / <?= e($popup['display_limit']) ?></td>
              <td><?= e($popup['created_at']) ?></td>
              <td>
                <form method="post" action="<?= e(route('admin.users.popup.end',['user'=>$user['id'],'notification'=>$popup['id']])) ?>" data-confirm="End this popup now? It will stop appearing immediately.">
                  <?= app('csrf')->input() ?>
                  <button class="button button-danger button-small" type="submit">End notification</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <p class="section-label">Direct email</p>
  <h2>Email this user</h2>
  <p class="muted">Send a message to <?= e($user['email']) ?> using the website mail configuration.</p>
  <form method="post" action="<?= e(route('admin.users.email',['user'=>$user['id']])) ?>" class="form-stack">
    <?= app('csrf')->input() ?>
    <label class="field">Subject<input name="subject" maxlength="190" required placeholder="Message from ApexTrades"></label>
    <label class="field">Message<textarea name="body" rows="7" maxlength="10000" required placeholder="Write your message to this user."></textarea></label>
    <button class="button" type="submit">Send email</button>
  </form>
</section>

<section class="card">
  <h2>Recent security events</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Event</th><th>IP</th><th>Time</th></tr></thead>
      <tbody>
      <?php foreach($events as $event): ?>
        <tr><td><?= e($event['event_type']) ?></td><td><?= e($event['ip_address']??'') ?></td><td><?= e($event['created_at']) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
