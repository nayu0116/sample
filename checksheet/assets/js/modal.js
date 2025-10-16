$(function () {
  // 変数に要素を入れる
  var open = $('.modal-open'),
    close = $('.cancel-btn'),
    container = $('.modal-bg');

  // 開くボタンをクリックしたらモーダルを表示する
  open.on('click', function () {
    container.addClass('active');
    return false;
  });

  // 閉じるボタンをクリックしたらモーダルを閉じる
  close.on('click', function () {
    container.removeClass('active');
  });
});
