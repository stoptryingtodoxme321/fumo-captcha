<?php

head('Verification');
?>
<main class="card">
  <div class="rcap-head">
    <div class="lead">Select all images with</div>
    <h1><strong><?= $label ?></strong></h1>
  </div>

  <form id="captcha" method="post" action="/captcha/check">
    <input type="hidden" name="cid" value="<?= $cid ?>">

    <fieldset style="border:0;padding:0;margin:0">
      <legend class="sr">Image selection grid</legend>
      <div class="grid" id="grid" style="--cols: <?= (int) $cols ?>">
        <?php foreach ($tiles as $i => $file): ?>
          <label class="tile">
            <input type="checkbox" name="pick[]" value="<?= e($file) ?>" autocomplete="off">
            <img src="/img/<?= $cid ?>/<?= rawurlencode($file) ?>"
                 alt="Tile <?= $i + 1 ?> of <?= count($tiles) ?>" draggable="false" loading="eager" decoding="async">
          </label>
        <?php endforeach ?>
      </div>
    </fieldset>

    <p class="err" id="status" role="status" aria-live="assertive"><?= $error ?></p>

    <div class="rcap-foot">
      <button type="button" class="icon-btn" id="replace" title="Get new images" aria-label="Get new images">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor"
          d="M17.65 6.35A7.96 7.96 0 0 0 12 4a8 8 0 1 0 7.73 10h-2.08A6 6 0 1 1 12 6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>
      </button>

      <button type="button" class="icon-btn" disabled title="No audio challenge" aria-label="No audio challenge">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor"
          d="M12 3a9 9 0 0 0-9 9v7a2 2 0 0 0 2 2h3v-8H5v-1a7 7 0 0 1 14 0v1h-3v8h3a2 2 0 0 0 2-2v-7a9 9 0 0 0-9-9z"/></svg>
      </button>

      <button type="button" class="icon-btn" title="Click every tile showing the character above" aria-label="Help">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor"
          d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
      </button>

      <span class="spacer"></span>
      <span class="sr" id="count" aria-live="polite">0 selected</span>

      <button class="go" type="submit" id="confirm">Confirm</button>
    </div>
  </form>
</main>
<script src="/captcha.js" defer></script>
<?php foot(); ?>
