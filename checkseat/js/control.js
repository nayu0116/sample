// JavaScript Document

// ログインアカウントを新規作成する場合パスワード入力欄を表示
$(function () {
  $('[name=class]').change(function () {
    var val = $('[name=class]').val();

    if (val === 'checkUserlist') {
      $('.login-pass').show();
    } else {
      $('.login-pass').hide();
    }
  }).trigger('change');
});

// 削除モーダル
document.addEventListener("DOMContentLoaded", function() {
  const modalText = document.getElementById("modal-text");
  const modalAccount = document.getElementById("modal-account");
  const modalUsername = document.getElementById("modal-username");

  document.querySelectorAll(".modal-open").forEach(button => {
    button.addEventListener("click", function() {
      const account = this.getAttribute("data-account");
      const username = this.getAttribute("data-username");

      modalText.textContent = `「${account}」から「${username}」を削除しますか？`;
      modalAccount.value = account;
      modalUsername.value = username;
    });
  });
});