(function () {
  "use strict";

  var preloader = document.querySelector(".js-preloader");
  if (!preloader) return;

  var timers = [];
  var reducedMotion =
    window.matchMedia &&
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function schedule(callback, delay) {
    var timer = window.setTimeout(callback, delay);
    timers.push(timer);
    return timer;
  }

  function revealContent() {
    preloader.classList.add("is-content-visible");
    document.body.classList.remove("is-loading");
  }

  function launchRocket() {
    preloader.classList.add("is-launching");
  }

  function finishIntro() {
    revealContent();
    preloader.classList.add("is-finished");
  }

  function resetIntro() {
    timers.forEach(function (timer) {
      window.clearTimeout(timer);
    });
    timers = [];
    preloader.classList.remove(
      "is-intro-controlled",
      "is-content-visible",
      "is-launching",
      "is-finished"
    );
  }

  window.AtomicsPreloaderIntro = {
    reset: resetIntro,
  };

  preloader.classList.add("is-intro-controlled");

  if (reducedMotion) {
    finishIntro();
    return;
  }

  /* Continutul devine disponibil repede; racheta continua independent. */
  schedule(revealContent, 380);
  schedule(launchRocket, 700);
  schedule(finishIntro, 1250);
})();
