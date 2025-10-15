$(document).ready(function () {
  $('.input-area').each(function () {
    var $this = $(this);
    if ($this.val()) $this.addClass('used');
  });

  $('.input-area').blur(function () {
    var $this = $(this);
    if ($this.val()) $this.addClass('used');
    else $this.removeClass('used');
  });
});
