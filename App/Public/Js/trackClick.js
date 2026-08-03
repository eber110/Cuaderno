function trackClick() {
  document.addEventListener('click', function(e) {
    const link = e.target.closest('.track-link-click');
    if (!link) return;

    const user = link.dataset.user;
    const linkId = link.dataset.linkId;

    if (user && linkId) {
      const payload = JSON.stringify({ user: user, linkId: linkId });

      if (navigator.sendBeacon) {
        navigator.sendBeacon('/op/track-click', payload);
      } else {
        fetch('/op/track-click', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: payload,
          keepalive: true
        }).catch(function() {});
      }
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', trackClick);
} else {
  trackClick();
}
