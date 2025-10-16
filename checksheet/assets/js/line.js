$(function () {
  //ページを読み込んだときの処理（7パターン分岐）

  // 対象のactionのIDを配列にまとめる
  const allActions = Array.from({ length: 50 }, (_, i) => `#action${i + 1}`);
  const evenActions = allActions.filter((_, index) => index % 2 !== 0); // 偶数のactionID
  const oddActions = allActions.filter((_, index) => index % 2 === 0); // 奇数のactionID

  // 1.「検査不要の指示なし」and「表項目が未入力」and「裏項目が未入力」
  if (!$('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() === '' && $('#ura[type=number]').val() === '') {
    // 全てのチェックボックスを非表示
    $(allActions.join(',')).hide();
  }

  // 2.「検査不要の指示なし」and「表項目が1以上」and「裏項目が未入力」
  if (!$('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() !== '' && $('#ura[type=number]').val() === '') {
    // 裏面のチェックボックスを非表示
    $(evenActions.join(',')).hide();
  }

  // 3.「検査不要の指示なし」and「表項目が未入力」and「裏項目が1以上」
  if (!$('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() === '' && $('#ura[type=number]').val() !== '') {
    // 表面のチェックボックスを非表示
    $(oddActions.join(',')).hide();
  }

  // 4.「検査不要の指示あり」and「表項目が未入力」and「裏項目が未入力」
  if ($('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() === '' && $('#ura[type=number]').val() === '') {
    // 検査項目B・Cを非表示
    $('.inspection-unnecessary').hide();
    // 全てのチェックボックスを非表示
    $(allActions.join(',')).hide();
  }

  // 5.「検査不要の指示あり」and「表項目が1以上」and「裏項目が未入力」
  if ($('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() !== '' && $('#ura[type=number]').val() === '') {
    // 検査項目B・Cを非表示
    $('.inspection-unnecessary').hide();
    // 裏面のチェックボックスを非表示
    $(evenActions.join(',')).hide();
  }

  // 6.「検査不要の指示あり」and「表項目が未入力」and「裏項目が1以上」
  if ($('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() === '' && $('#ura[type=number]').val() !== '') {
    // 検査項目B・Cを非表示
    $('.inspection-unnecessary').hide();
    // 表面のチェックボックスを非表示
    $(oddActions.join(',')).hide();
  }

  // 7.「検査不要の指示あり」and「表項目が1以上」and「裏項目が1以上」
  if ($('#unnecessaryBtn').is(':checked') && $('#omote[type=number]').val() !== '' && $('#ura[type=number]').val() !== '') {
    // 検査項目B・Cを非表示
    $('.inspection-unnecessary').hide();
  }

  // 表示後の処理
  // 表項目に入力されたときの処理
  $('#omote[type=number]').on('input', function () {
    // 表面のチェックボックスを表示⇄非表示
    $(oddActions.join(',')).toggle($(this).val() !== '');
  });

  // 裏項目に入力されたときの処理
  $('#ura[type=number]').on('input', function () {
    // 裏面のチェックボックスを表示⇄非表示
    $(evenActions.join(',')).toggle($(this).val() !== '');
  });

  // 検査不要チェックボックス基準の処理
  $('#unnecessaryBtn').on('change', function () {
    // 検査項目B・Cを表示⇄非表示
    $('.inspection-unnecessary').toggle();
  });
});
