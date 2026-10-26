<div id="cookieNotice" class="cookie wrap" hidden role="dialog" aria-labelledby="cookieTitle">
  <p id="cookieTitle">ATLAS does not use advertising or analytics cookies. A session cookie is set only after you sign in. A watchlist you have not saved to an account stays in this browser. Paddle sets its own cookies on the payment page. <a href="/legal#cookies">Privacy</a></p>
  <button class="btn gold sm" type="button" id="cookieOk">Understood</button>
</div>
<script>
(function () {
  var box = document.getElementById("cookieNotice");
  if (!box) return;
  try { if (localStorage.getItem("ssa:cookieNotice")) return; } catch (e) { return; }
  box.hidden = false;
  document.getElementById("cookieOk").addEventListener("click", function () {
    try { localStorage.setItem("ssa:cookieNotice", "1"); } catch (e) {}
    box.hidden = true;
  });
})();
</script>
