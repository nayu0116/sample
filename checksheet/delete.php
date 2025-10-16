<?php
// セッションスタート
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.gc_divisor', 1);
session_start();
// セッションIDを新しいものに置き換える（★セッションハイジャック）
session_regenerate_id();
// クリックジャッキング対策
header("X-FRAME-OPTIONS: DENY");

// ログインしていないorログインから10時間以上経過していればログイン画面へ強制リダイレクト
if (!isset($_SESSION['loginName']) || (time() - $_SESSION["time"] > 36000)) {
  header('Location: ./login.php');
  exit();
}

// dbconnect.phpを読み込む
require_once 'dbconnect.php';
// データベースに接続する
$pdo = dbConnect();

if (isset($_POST['delete'])) {
  if (!isset($_POST['session_token']) || $_POST['session_token'] !== $_SESSION['session_token']) {

    $_SESSION['error-message'] = '正しい手順で再度操作をお願いします';
    header('Location: ./index.php');
    exit();
  } else {

    try {
      // POSTデータの取得
      $name = $_POST['username'];
      $account = $_POST['account'];

      // 許可するテーブル名を定義
      $allowed_tables = ["checkUserlist", "kensa_sekinin", "kensa_tantou", "sagyou_tantou"];

      $account_list = [
        'アカウント' => 'checkUserlist',
        '検査責任者' => 'kensa_sekinin',
        '検査担当者' => 'kensa_tantou',
        '作業担当者' => 'sagyou_tantou'
      ];

      // データベースから削除
      if (in_array($account_list[$account], $allowed_tables)) {
        $sql = "DELETE FROM " . $account_list[$account] . " WHERE name = :name";
        $stmt = $pdo->prepare($sql);
        // プレースホルダーに値をセット
        $stmt->bindParam(":name", $name, PDO::PARAM_STR);
        // SQL実行
        $stmt->execute();
        // 管理画面を再読み込み
        $_SESSION['msg'] = "アカウント情報を削除しました";
        header('Location: ./control.php');
        exit();
      } else {
        $_SESSION['err_msg'] = 'テーブル名が存在しません';
        header('Location: ./control.php');
        exit();
      }
    } catch (PDOException $e) {
      echo '接続失敗' . $e->getMessage();
      exit();
    }
    // DBとの接続を切る
    $pdo = null;
    $stmt = null;
  }
}
