if (typeof AOS !== 'undefined') {
  AOS.init({
    duration: 1000,
    once: true
  });
}

window.onscroll = function () {
  scrollFunction();
};

function scrollFunction() {
  const btn = document.getElementById("backToTop");
  if (btn) {
    if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
      btn.style.display = "block";
    } else {
      btn.style.display = "none";
    }
  }
}

function topFunction() {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.topFunction = topFunction;