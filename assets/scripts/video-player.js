// ──────────────────────────────────────────────────────────────────────────
// Videokurz — přehrávač YouTube až na kliknutí.
//
// Stránka nese jen náhled z vlastního serveru ([data-vplayer]). Po kliknutí
// se místo něj vloží <iframe> z youtube-nocookie.com, takže bez interakce se
// nenačítají cizí skripty ani cookies (a CSP povoluje jen tenhle frame-src).
//
// [data-vplayer-seek="123"] (+ aria-controls=id přehrávače) skočí na čas:
// před spuštěním přehrávač rovnou načte od dané sekundy, za běhu pošle
// příkaz přes IFrame API (postMessage; proto enablejsapi=1).
//
// [data-vchap] je blok v kapitole: tlačítka [data-vchap-pick] vyberou díl,
// rozbalí nad seznamem jeho přehrávač a spustí ho.
// ──────────────────────────────────────────────────────────────────────────

(function () {
  const ORIGIN = 'https://www.youtube-nocookie.com';

  function load(player, start) {
    const id = player.getAttribute('data-yt');
    if (!id) return null;
    let frame = player.querySelector('iframe');
    if (frame) return frame;

    const params = new URLSearchParams({
      autoplay: '1',
      rel: '0',
      playsinline: '1',
      enablejsapi: '1',
      origin: window.location.origin,
    });
    if (start) params.set('start', String(start));

    frame = document.createElement('iframe');
    frame.src = ORIGIN + '/embed/' + encodeURIComponent(id) + '?' + params.toString();
    frame.title = 'Přehrávač YouTube: ' + (player.getAttribute('data-title') || 'video');
    frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen; web-share';
    frame.allowFullscreen = true;
    frame.referrerPolicy = 'strict-origin-when-cross-origin';
    frame.className = 'vplayer-frame';

    const poster = player.querySelector('[data-vplayer-play]');
    player.appendChild(frame);
    player.classList.add('is-playing');
    if (poster) poster.remove();
    frame.focus();
    return frame;
  }

  function command(frame, func, args) {
    if (!frame || !frame.contentWindow) return;
    frame.contentWindow.postMessage(JSON.stringify({ event: 'command', func: func, args: args || [] }), ORIGIN);
  }

  function seek(player, seconds) {
    const frame = player.querySelector('iframe');
    if (!frame) {
      load(player, seconds);
    } else {
      command(frame, 'seekTo', [seconds, true]);
      command(frame, 'playVideo');
    }
    const rect = player.getBoundingClientRect();
    if (rect.top < 0 || rect.bottom > window.innerHeight) {
      player.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
    }
  }

  function pick(section, ep) {
    const stage = section.querySelector('[data-vchap-stage]');
    if (!stage) return;
    stage.hidden = false;
    section.querySelectorAll('[data-vchap-slot]').forEach(function (slot) {
      const active = slot.getAttribute('data-vchap-slot') === ep;
      if (!active) {
        // Skrytý přehrávač zastavit, ať dva díly nehrají přes sebe.
        command(slot.querySelector('iframe'), 'pauseVideo');
      }
      slot.hidden = !active;
    });
    section.querySelectorAll('[data-vchap-pick]').forEach(function (btn) {
      const active = btn.getAttribute('data-vchap-pick') === ep;
      btn.setAttribute('aria-expanded', active ? 'true' : 'false');
      btn.closest('.vchap-item')?.classList.toggle('is-active', active);
    });
    const slot = section.querySelector('[data-vchap-slot="' + ep + '"]');
    const player = slot && slot.querySelector('[data-vplayer]');
    if (player) load(player, 0);
  }

  document.addEventListener('click', function (e) {
    const play = e.target.closest('[data-vplayer-play]');
    if (play) {
      load(play.closest('[data-vplayer]'), 0);
      return;
    }

    const seekBtn = e.target.closest('[data-vplayer-seek]');
    if (seekBtn) {
      const player = document.getElementById(seekBtn.getAttribute('aria-controls') || '');
      if (player) seek(player, parseInt(seekBtn.getAttribute('data-vplayer-seek'), 10) || 0);
      return;
    }

    const pickBtn = e.target.closest('[data-vchap-pick]');
    if (pickBtn && pickBtn.tagName === 'BUTTON') {
      pick(pickBtn.closest('[data-vchap]'), pickBtn.getAttribute('data-vchap-pick'));
    }
  });
})();
