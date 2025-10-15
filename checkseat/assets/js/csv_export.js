$(function () {
  $('#csv').click(function () {
    var data = [];

    $('table tr').each(function (i, row) {
      var rowData = [];
      var cells = $(row).find('th, td');

      cells.each(function (j, cell) {
        // 最後のセル（td or th）ならスキップ！
        if (j === cells.length - 1) return;

        var text = $(cell).text().trim();

        // カンマ・ダブルクォーテーション処理
        if (text.includes(',') || text.includes('"')) {
          text = '"' + text.replace(/"/g, '""') + '"';
        }
        rowData.push(text);
      });
      data.push(rowData);
    });

    // BOMとCSVデータ作成
    var bom = new Uint8Array([0xef, 0xbb, 0xbf]);
    var csv_data = data.map((row) => row.join(',')).join('\r\n');
    var blob = new Blob([bom, csv_data], { type: 'text/csv' });
    var url = URL.createObjectURL(blob);

    // ダウンロード
    var a = document.getElementById('downloader');
    a.href = url;
    a.download = 'data.csv';
    a.click();
  });
});
