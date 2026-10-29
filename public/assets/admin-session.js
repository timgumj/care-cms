"use strict";
// Both EasyAdmin menus render sign-out links; submit the protected POST form instead.
document.addEventListener('click', event => {
  const link = event.target.closest('a[href]');
  const form = document.getElementById('admin-logout-form');
  if (!link || !form || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
  const target = new URL(link.href, location.href);
  const logout = new URL(form.action, location.href);
  if (target.origin !== logout.origin || target.pathname !== logout.pathname) return;
  event.preventDefault();
  form.requestSubmit();
});
