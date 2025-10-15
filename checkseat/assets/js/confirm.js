// JavaScript Document

$(function () {
  $('#confirmBtn').on('change', function () {
    const fields = ['type', 'orisuu', 'omote', 'ura'];

    fields.forEach((field) => {
      const input = $(`#${field}`);
      const line = $(`.${field}_line`);

      if ($(this).is(':checked') && input.val() === '') {
        line.addClass('line');
        input.prop('readonly', true);
      } else {
        line.removeClass('line');
        input.prop('readonly', false);
      }
    });

    if ($(this).is(':checked')) {
      $('.checklist input[type="checkbox"]:not(:checked)').each(function () {
        const checklist = $(this).closest('.checklist');
        const parent = checklist.parent(); // .front や .back

        if (parent.hasClass('front') || parent.hasClass('back')) {
          parent.addClass('slash');

          // セレクトとチェックボックスを操作不能に（送信は可能）
          parent.find('select, input[type="checkbox"]').addClass('noclick');
        }
      });
    } else {
      // .slashクラスを外して、操作できるように戻す
      $('.front.slash, .back.slash').each(function () {
        $(this).removeClass('slash').find('select, input[type="checkbox"]').removeClass('noclick');
      });
    }

    // 登録ボタンの動き
    $('#submitBtn').prop('disabled', !$(this).is(':checked'));
    // 印刷リンクの有効／無効切り替え
    if ($(this).is(':checked')) {
      $('.print').removeClass('disabled-print');
    } else {
      $('.print').addClass('disabled-print');
    }
  });
});
