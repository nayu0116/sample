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

//ワンタイムトークン生成（URL直打ち対策用）
$token = openssl_random_pseudo_bytes(16);
$session_token = bin2hex($token);
$_SESSION['session_token'] = $session_token;

// dbconnect.phpを読み込む
require_once 'dbconnect.php';

// HTMLエスケープ
function h($s)
{
  return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}
// ログイン中のユーザー情報を表示（★クロスサイトスクリプティング）
$loginName = h($_SESSION['loginName']);

if (isset($_GET['id'])) {
  try {
    // データベースに接続する
    $pdo = dbConnect();

    // checkseatテーブルから1件取得
    $sql = "SELECT * FROM checkseat WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    $work = $stmt->fetch(PDO::FETCH_OBJ); // 1件のレコードを取得

    // セッションに値を保存
    $_SESSION['id'] = $_GET['id'];
    $_SESSION['url'] = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // checkseat の配列変換
    $number_array = explode('-', $work->number);
    $triangles_array = explode(', ', $work->triangles);
    $checklists_array = explode(', ', $work->checklists);

    $number_keys = ["sales", "year", "orders"];
    $triangles_keys = range(1, count($triangles_array));
    $checklists_keys = range(1, count($checklists_array));

    $number = array_combine($number_keys, $number_array);
    $triangles = array_combine($triangles_keys, $triangles_array);
    $checklists = array_combine($checklists_keys, $checklists_array);

    // 追加で各担当者テーブルのname一覧を取得
    $tables = ['kensa_sekinin', 'kensa_tantou', 'sagyou_tantou'];
    $data = [];

    foreach ($tables as $table) {
      $sql = "SELECT name FROM $table";
      $stmt = $pdo->prepare($sql);
      $stmt->execute();
      $data[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    //データベース切断
    $pdo = null;
  } catch (PDOException $e) {
    exit('データベースに接続できませんでした' . $e->getMessage());
  }
}
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, user-scalable=yes">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./assets/css/normalize.css">
  <link rel="stylesheet" href="./assets/css/styleseat.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="./assets/css/print.css" media="print" type="text/css" />
  <title>情報加工検査チェックシート</title>
</head>

<body>
  <header id="header">
    <div class="inner">
      <h2>情報加工検査チェックシート</h2>
      <form class="account-menu" action="#" method="post">
        <span class="account"><?= $loginName; ?>でログイン中</span>
        <a href="./search.php" class="top-btn">検索画面へ戻る</a>
        <?php
        // 不正リクエストチェック用のトークン生成（★CSRF）
        $token = sha1(uniqid(mt_rand(), true));
        $_SESSION['logout_token'] = $token;
        echo '<input type="hidden" name="logout_token" value="' . $token . '" />';
        ?>
      </form>
    </div>
  </header>
  <main class="container">
    <div class="inner">
      <form id="checksheet" method="POST" action="update.php">
        <div class="form">
          <div class="form-flex">
            <dl class="flex-item print-flex-item">
              <dt class="label">
                <label for="Aday">検査A実施日</label>
              </dt>
              <dd class="box">
                <input class="input" type="date" name="Aday" id="Aday" value="<?= h($work->Aday ?? '') ?>">
              </dd>
            </dl>
            <dl class="flex-item print-flex-item">
              <dt class="label">
                <label for="BCday">検査B・C実施日</label>
              </dt>
              <dd class="box">
                <input class="input" type="date" name="BCday" id="BCday" value="<?= h($work->BCday ?? '') ?>">
              </dd>
            </dl>
          </div>
          <dl class="item print-flex-item">
            <dt class="label">
              <label for="kousei">校正</label>
            </dt>
            <dd class="box select">
              <input class="datalist input" type="text" name="kousei" id="kousei" list="kousei-list" placeholder="選択または入力してください" autocomplete="off" value="<?= h($work->kousei ?? '') ?>">
              <datalist id="kousei-list">
                <option value="初校"></option>
                <option value="再校"></option>
                <option value="三校"></option>
                <option value="四校"></option>
                <option value="下版"></option>
              </datalist>
            </dd>
          </dl>
          <dl class="item print-flex-item">
            <dt class="label">
              <label for="number">受注番号</label>
            </dt>
            <dd class="box number select">
              <input class="datalist input" type="text" name="number[sales]" id="number" list="sales" placeholder="選択または入力" autocomplete="off" value="<?= h($number['sales'] ?? '') ?>">
              <datalist id="sales">
                <option value="KB"></option>
                <option value="KC"></option>
                <option value="KD"></option>
                <option value="KJ"></option>
                <option value="KO"></option>
                <option value="KR"></option>
                <option value="KT"></option>
                <option value="KV"></option>
                <option value="KW"></option>
                <option value="KX"></option>
                <option value="KZ"></option>
              </datalist>-
              <input class="input" type="number" name="number[year]" min="1" step="1" autocomplete="off" value="<?= h($number['year'] ?? '') ?>">-
              <input class="input" type="number" name="number[orders]" min="1" step="1" autocomplete="off" value="<?= h($number['orders'] ?? '') ?>">
            </dd>
          </dl>
          <div class="form-flex">
            <dl class="flex-item print-flex-item">
              <dt class="label">
                <label for="type">タイプ</label>
              </dt>
              <dd class="box">
                <input class="input" type="text" id="type" name="type" value="<?= h($work->type ?? '') ?>">
              </dd>
            </dl>
            <dl class="flex-item print-flex-item">
              <dt class="label">
                <label for="naikou">グリーン・内校</label>
              </dt>
              <dd class="box select">
                <select name="naikou" id="naikou">
                  <option value="" hidden>選択してください</option>
                  <option value="グリーン" <?= h($work->naikou) === 'グリーン' ? 'selected' : '' ?>>グリーン</option>
                  <option value="内校" <?= h($work->naikou) === '内校' ? 'selected' : '' ?>>内校</option>
                </select>
              </dd>
            </dl>
          </div>
          <dl class="item print-flex-item">
            <dt class="label">
              <label for="hinmei">品名</label>
            </dt>
            <dd class="box">
              <input class="input" type="text" name="hinmei" id="hinmei" autocomplete="off" value="<?= h($work->hinmei ?? '') ?>">
            </dd>
          </dl>
          <div class="trisection">
            <dl class="trisection-item">
              <dd class="box">
                <input class="input" id="orisuu" type="number" name="orisuu" min="1" step="1" value="<?= h($work->orisuu ?? '') ?>">
                <span class="unit">折</span>
              </dd>
            </dl>
            <dl class="trisection-item print-flex-item">
              <dt class="label">
                <label for="omote">表</label>
              </dt>
              <dd class="box">
                <input class="input" id="omote" type="number" name="omote" min="1" step="1" value="<?= h($work->omote ?? '') ?>">
              </dd>
            </dl>
            <dl class="trisection-item print-flex-item">
              <dt class="label">
                <label for="ura">裏</label>
              </dt>
              <dd class="box">
                <input class="input" id="ura" type="number" name="ura" min="1" step="1" value="<?= h($work->ura ?? '') ?>">
              </dd>
            </dl>
          </div>
          <div class="kensa">
            <label class="kensa-check">
              <input type="hidden" name="kensa" value="検査不要の指示なし">
              <input id="unnecessaryBtn" type="checkbox" name="kensa" value="検査不要の指示あり" <?= h($work->kensa) === '検査不要の指示あり' ? 'checked' : '' ?>>
              <p class="kensa-text">検査不要の指示あり</p>
            </label>
            <span>※情報加工指示書及び責了付箋の「検査不要」欄にチェックがある場合のみ、レ点を入れること</span>
          </div>
        </div>

        <div class="checksheet">
          <p>検査不要の指示がある場合は、検査はAのみとし、B及びCは省きます</p>
          <dl class="check-list">
            <dt class="item-title">A必須検査項目</dt>
            <dd class="list-item">
              <p class="check-item">情報加工指示書・別紙明細・見本・責了付箋など必要指示が全て揃っているかの確認</p>
              <div class="check">
                <div id="action1" class="front">
                  <div class="triangle triangle1 action1 line1">
                    <select class="triangle-box action1" name="triangles[1]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[1]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action1 line1">
                    <input type="hidden" name="checklists[1]" value="未チェック">
                    <input type="checkbox" name="checklists[1]" value="チェック済" <?= h($checklists[1]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action2" class="back">
                  <div class="triangle triangle2 action2 line2">
                    <select class="triangle-box action2" name="triangles[2]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[2]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action2 line2">
                    <input type="hidden" name="checklists[2]" value="未チェック">
                    <input type="checkbox" name="checklists[2]" value="チェック済" <?= h($checklists[2]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">入稿データが正常に開くかの確認（※ネイティブデータにて入稿の場合）</p>
              <div class="check">
                <div id="action3" class="front">
                  <div class="triangle triangle3 action3 line3">
                    <select class="triangle-box action3" name="triangles[3]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[3]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action3 line3">
                    <input type="hidden" name="checklists[3]" value="未チェック">
                    <input type="checkbox" name="checklists[3]" value="チェック済" <?= h($checklists[3]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action4" class="back">
                  <div class="triangle triangle4 action4 line4">
                    <select class="triangle-box action4" name="triangles[4]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[4]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action4 line4">
                    <input type="hidden" name="checklists[4]" value="未チェック">
                    <input type="checkbox" name="checklists[4]" value="チェック済" <?= h($checklists[4]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">入稿データが指示通りの内容（版名・ファイル数）で揃っているかの確認</p>
              <div class="check">
                <div id="action5" class="front">
                  <div class="triangle triangle5 action5 line5">
                    <select class="triangle-box action5" name="triangles[5]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[5]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action5 line5">
                    <input type="hidden" name="checklists[5]" value="未チェック">
                    <input type="checkbox" name="checklists[5]" value="チェック済" <?= h($checklists[5]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action6" class="back">
                  <div class="triangle triangle6 action6 line6">
                    <select class="triangle-box action6" name="triangles[6]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[6]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action6 line6">
                    <input type="hidden" name="checklists[6]" value="未チェック">
                    <input type="checkbox" name="checklists[6]" value="チェック済" <?= h($checklists[6]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">受注番号・品名・サイズ（仕上がり寸法）の確認</p>
              <div class="check">
                <div id="action7" class="front">
                  <div class="triangle triangle7 action7 line7">
                    <select class="triangle-box action7" name="triangles[7]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[7]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action7 line7">
                    <input type="hidden" name="checklists[7]" value="未チェック">
                    <input type="checkbox" name="checklists[7]" value="チェック済" <?= h($checklists[7]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action8" class="back">
                  <div class="triangle triangle8 action8 line8">
                    <select class="triangle-box action8" name="triangles[8]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[8]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action8 line8">
                    <input type="hidden" name="checklists[8]" value="未チェック">
                    <input type="checkbox" name="checklists[8]" value="チェック済" <?= h($checklists[8]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">全てのファイルが正常にRIP演算できたかの確認</p>
              <div class="check">
                <div id="action9" class="front">
                  <div class="triangle triangle9 solid-action9 line9">
                    <select class="triangle-box action9" name="triangles[9]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[9]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist solid-action9 line9">
                    <input type="hidden" name="checklists[9]" value="未チェック">
                    <input type="checkbox" name="checklists[9]" value="チェック済" <?= h($checklists[9]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action10" class="back">
                  <div class="triangle triangle10 solid-action10 line10">
                    <select class="triangle-box action10" name="triangles[10]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[10]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist solid action10 line10">
                    <input type="hidden" name="checklists[10]" value="未チェック">
                    <input type="checkbox" name="checklists[10]" value="チェック済" <?= h($checklists[10]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
          </dl>
          <dl class="check-list inspection-unnecessary">
            <dt class="item-title">B通常検査項目</dt>
            <dd class="list-item">
              <p class="check-item">修正箇所の確認</p>
              <div class="check">
                <div id="action11" class="front">
                  <div class="triangle triangle11 action11 line11">
                    <select class="triangle-box action11" name="triangles[11]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[11]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action11 line11">
                    <input type="hidden" name="checklists[11]" value="未チェック">
                    <input type="checkbox" name="checklists[11]" value="チェック済" <?= h($checklists[11]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action12" class="back">
                  <div class="triangle triangle12 action12 line12">
                    <select class="triangle-box action12" name="triangles[12]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[12]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action12 line12">
                    <input type="hidden" name="checklists[12]" value="未チェック">
                    <input type="checkbox" name="checklists[12]" value="チェック済" <?= h($checklists[12]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">ヨゴレの確認</p>
              <div class="check">
                <div id="action13" class="front">
                  <div class="triangle triangle13 action13 line13">
                    <select class="triangle-box action13" name="triangles[13]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[13]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action13 line13">
                    <input type="hidden" name="checklists[13]" value="未チェック">
                    <input type="checkbox" name="checklists[13]" value="チェック済" <?= h($checklists[13]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action14" class="back">
                  <div class="triangle triangle14 action14 line14">
                    <select class="triangle-box action14" name="triangles[14]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[14]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action14 line14">
                    <input type="hidden" name="checklists[14]" value="未チェック">
                    <input type="checkbox" name="checklists[14]" value="チェック済" <?= h($checklists[14]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">アミ欠け・絵柄抜けの確認</p>
              <div class="check">
                <div id="action15" class="front">
                  <div class="triangle triangle15 action15 line15">
                    <select class="triangle-box action15" name="triangles[15]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[15]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action15 line15">
                    <input type="hidden" name="checklists[15]" value="未チェック">
                    <input type="checkbox" name="checklists[15]" value="チェック済" <?= h($checklists[15]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action16" class="back">
                  <div class="triangle triangle16 action16 line16">
                    <select class="triangle-box action16" name="triangles[16]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[16]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action16 line16">
                    <input type="hidden" name="checklists[16]" value="未チェック">
                    <input type="checkbox" name="checklists[16]" value="チェック済" <?= h($checklists[16]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">文字欠けの確認</p>
              <div class="check">
                <div id="action17" class="front">
                  <div class="triangle triangle17 action17 line17">
                    <select class="triangle-box action17" name="triangles[17]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[17]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action17 line17">
                    <input type="hidden" name="checklists[17]" value="未チェック">
                    <input type="checkbox" name="checklists[17]" value="チェック済" <?= h($checklists[17]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action18" class="back">
                  <div class="triangle triangle18 action18 line18">
                    <select class="triangle-box action18" name="triangles[18]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[18]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action18 line18">
                    <input type="hidden" name="checklists[18]" value="未チェック">
                    <input type="checkbox" name="checklists[18]" value="チェック済" <?= h($checklists[18]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">トンボ位置の確認</p>
              <div class="check">
                <div id="action19" class="front">
                  <div class="triangle triangle19 action19 line19">
                    <select class="triangle-box action19" name="triangles[19]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[19]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action19 line19">
                    <input type="hidden" name="checklists[19]" value="未チェック">
                    <input type="checkbox" name="checklists[19]" value="チェック済" <?= h($checklists[19]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action20" class="back">
                  <div class="triangle triangle20 action20 line20">
                    <select class="triangle-box action20" name="triangles[20]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[20]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action20 line20">
                    <input type="hidden" name="checklists[20]" value="未チェック">
                    <input type="checkbox" name="checklists[20]" value="チェック済" <?= h($checklists[20]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">裁ちから2ミリ以内に必要な情報がないかの確認</p>
              <div class="check">
                <div id="action21" class="front">
                  <div class="triangle triangle21 action21 line21">
                    <select class="triangle-box action21" name="triangles[21]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[21]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action21 line21">
                    <input type="hidden" name="checklists[21]" value="未チェック">
                    <input type="checkbox" name="checklists[21]" value="チェック済" <?= h($checklists[21]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action22" class="back">
                  <div class="triangle triangle22 action22 line22">
                    <select class="triangle-box action22" name="triangles[22]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[22]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action22 line22">
                    <input type="hidden" name="checklists[22]" value="未チェック">
                    <input type="checkbox" name="checklists[22]" value="チェック済" <?= h($checklists[22]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">塗り足しの確認</p>
              <div class="check">
                <div id="action23" class="front">
                  <div class="triangle triangle23 action23 line23">
                    <select class="triangle-box action23" name="triangles[23]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[23]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action23 line23">
                    <input type="hidden" name="checklists[23]" value="未チェック">
                    <input type="checkbox" name="checklists[23]" value="チェック済" <?= h($checklists[23]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action24" class="back">
                  <div class="triangle triangle24 action24 line24">
                    <select class="triangle-box action24" name="triangles[24]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[24]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action24 line24">
                    <input type="hidden" name="checklists[24]" value="未チェック">
                    <input type="checkbox" name="checklists[24]" value="チェック済" <?= h($checklists[24]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">面付表との確認（天地左右のサイズ、背標、背丁、足標、ドブ幅、ノンブル）</p>
              <div class="check">
                <div id="action25" class="front">
                  <div class="triangle triangle25 action25 line25">
                    <select class="triangle-box action25" name="triangles[25]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[25]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action25 line25">
                    <input type="hidden" name="checklists[25]" value="未チェック">
                    <input type="checkbox" name="checklists[25]" value="チェック済" <?= h($checklists[25]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action26" class="back">
                  <div class="triangle triangle26 action26 line26">
                    <select class="triangle-box action26" name="triangles[26]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[26]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action26 line26">
                    <input type="hidden" name="checklists[26]" value="未チェック">
                    <input type="checkbox" name="checklists[26]" value="チェック済" <?= h($checklists[26]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">折トンボに罫線を引くまたは折トンボで折ってみて折り位置が正しいかの確認</p>
              <div class="check">
                <div id="action27" class="front">
                  <div class="triangle triangle27 action27 line27">
                    <select class="triangle-box action27" name="triangles[27]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[27]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action27 line27">
                    <input type="hidden" name="checklists[27]" value="未チェック">
                    <input type="checkbox" name="checklists[27]" value="チェック済" <?= h($checklists[27]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action28" class="back">
                  <div class="triangle triangle28 action28 line28">
                    <select class="triangle-box action28" name="triangles[28]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[28]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action28 line28">
                    <input type="hidden" name="checklists[28]" value="未チェック">
                    <input type="checkbox" name="checklists[28]" value="チェック済" <?= h($checklists[28]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">トムソンの確認</p>
              <div class="check">
                <div id="action29" class="front">
                  <div class="triangle triangle29 action29 line29">
                    <select class="triangle-box action29" name="triangles[29]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[29]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action29 line29">
                    <input type="hidden" name="checklists[29]" value="未チェック">
                    <input type="checkbox" name="checklists[29]" value="チェック済" <?= h($checklists[29]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action30" class="back">
                  <div class="triangle triangle30 action30 line30">
                    <select class="triangle-box action30" name="triangles[30]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[30]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action30 line30">
                    <input type="hidden" name="checklists[30]" value="未チェック">
                    <input type="checkbox" name="checklists[30]" value="チェック済" <?= h($checklists[30]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">品名ラベルで最新版であるかの確認</p>
              <div class="check">
                <div id="action31" class="front">
                  <div class="triangle triangle31 action31 line31">
                    <select class="triangle-box action31" name="triangles[31]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[31]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action31 line31">
                    <input type="hidden" name="checklists[31]" value="未チェック">
                    <input type="checkbox" name="checklists[31]" value="チェック済" <?= h($checklists[31]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action32" class="back">
                  <div class="triangle triangle32 action32 line32">
                    <select class="triangle-box action32" name="triangles[32]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[32]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action32 line32">
                    <input type="hidden" name="checklists[32]" value="未チェック">
                    <input type="checkbox" name="checklists[32]" value="チェック済" <?= h($checklists[32]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">見開き位置の確認</p>
              <div class="check">
                <div id="action33" class="front">
                  <div class="triangle triangle33 action33 line33">
                    <select class="triangle-box action33" name="triangles[33]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[33]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action33 line33">
                    <input type="hidden" name="checklists[33]" value="未チェック">
                    <input type="checkbox" name="checklists[33]" value="チェック済" <?= h($checklists[33]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action34" class="back">
                  <div class="triangle triangle34 action34 line34">
                    <select class="triangle-box action34" name="triangles[34]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[34]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action34 line34">
                    <input type="hidden" name="checklists[34]" value="未チェック">
                    <input type="checkbox" name="checklists[34]" value="チェック済" <?= h($checklists[34]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">添付された校正紙と突き合わせ修正箇所以外の変化（バグによる変化など）がないかの確認</p>
              <div class="check">
                <div id="action35" class="front">
                  <div class="triangle triangle35 action35 line35">
                    <select class="triangle-box action35" name="triangles[35]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[35]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action35 line35">
                    <input type="hidden" name="checklists[35]" value="未チェック">
                    <input type="checkbox" name="checklists[35]" value="チェック済" <?= h($checklists[35]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action36" class="back">
                  <div class="triangle triangle36 action36 line36">
                    <select class="triangle-box action36" name="triangles[36]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[36]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action36 line36">
                    <input type="hidden" name="checklists[36]" value="未チェック">
                    <input type="checkbox" name="checklists[36]" value="チェック済" <?= h($checklists[36]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">最終原稿と付け合せする出力紙の番号が合っているかの確認</p>
              <div class="check">
                <div id="action37" class="front">
                  <div class="triangle triangle37 action37 line37">
                    <select class="triangle-box action37" name="triangles[37]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[37]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action37 line37">
                    <input type="hidden" name="checklists[37]" value="未チェック">
                    <input type="checkbox" name="checklists[37]" value="チェック済" <?= h($checklists[37]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action38" class="back">
                  <div class="triangle triangle38 action38 line38">
                    <select class="triangle-box action38" name="triangles[38]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[38]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action38 line38">
                    <input type="hidden" name="checklists[38]" value="未チェック">
                    <input type="checkbox" name="checklists[38]" value="チェック済" <?= h($checklists[38]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">検版ソフトでの確認</p>
              <div class="check">
                <div id="action39" class="front">
                  <div class="triangle triangle39 solid-action39 line39">
                    <select class="triangle-box action39" name="triangles[39]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[39]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist solid-action39 line39">
                    <input type="hidden" name="checklists[39]" value="未チェック">
                    <input type="checkbox" name="checklists[39]" value="チェック済" <?= h($checklists[39]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action40" class="back">
                  <div class="triangle triangle40 solid-action40 line40">
                    <select class="triangle-box action40" name="triangles[40]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[40]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist solid action40 line40">
                    <input type="hidden" name="checklists[40]" value="未チェック">
                    <input type="checkbox" name="checklists[40]" value="チェック済" <?= h($checklists[40]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
          </dl>
          <dl class="check-list inspection-unnecessary">
            <dt class="item-title">C特別検査項目</dt>
            <dd class="list-item">
              <p class="check-item">カレンダー日付・曜日・六曜の確認</p>
              <div class="check">
                <div id="action41" class="front">
                  <div class="triangle triangle41 action41 line41">
                    <select class="triangle-box action41" name="triangles[41]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[41]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action41 line41">
                    <input type="hidden" name="checklists[41]" value="未チェック">
                    <input type="checkbox" name="checklists[41]" value="チェック済" <?= h($checklists[41]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action42" class="back">
                  <div class="triangle triangle42 action42 line42">
                    <select class="triangle-box action42" name="triangles[42]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[42]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action42 line42">
                    <input type="hidden" name="checklists[42]" value="未チェック">
                    <input type="checkbox" name="checklists[42]" value="チェック済" <?= h($checklists[42]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">台割りまたは面付表どおりのノンブルになっているか、位置は正しいかの確認</p>
              <div class="check">
                <div id="action43" class="front">
                  <div class="triangle triangle43 action43 line43">
                    <select class="triangle-box action43" name="triangles[43]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[43]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action43 line43">
                    <input type="hidden" name="checklists[43]" value="未チェック">
                    <input type="checkbox" name="checklists[43]" value="チェック済" <?= h($checklists[43]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action44" class="back">
                  <div class="triangle triangle44 action44 line44">
                    <select class="triangle-box action44" name="triangles[44]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[44]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action44 line44">
                    <input type="hidden" name="checklists[44]" value="未チェック">
                    <input type="checkbox" name="checklists[44]" value="チェック済" <?= h($checklists[44]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">誤字・脱字の確認</p>
              <div class="check">
                <div id="action45" class="front">
                  <div class="triangle triangle45 action45 line45">
                    <select class="triangle-box action45" name="triangles[45]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[45]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action45 line45">
                    <input type="hidden" name="checklists[45]" value="未チェック">
                    <input type="checkbox" name="checklists[45]" value="チェック済" <?= h($checklists[45]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action46" class="back">
                  <div class="triangle triangle46 action46 line46">
                    <select class="triangle-box action46" name="triangles[46]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[46]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action46 line46">
                    <input type="hidden" name="checklists[46]" value="未チェック">
                    <input type="checkbox" name="checklists[46]" value="チェック済" <?= h($checklists[46]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">文字の重なり・太り・かすれ・つぶれの確認</p>
              <div class="check">
                <div id="action47" class="front">
                  <div class="triangle triangle47 action47 line47">
                    <select class="triangle-box action47" name="triangles[47]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[47]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action47 line47">
                    <input type="hidden" name="checklists[47]" value="未チェック">
                    <input type="checkbox" name="checklists[47]" value="チェック済" <?= h($checklists[47]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action48" class="back">
                  <div class="triangle triangle48 action48 line48">
                    <select class="triangle-box action48" name="triangles[48]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[48]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action48 line48">
                    <input type="hidden" name="checklists[48]" value="未チェック">
                    <input type="checkbox" name="checklists[48]" value="チェック済" <?= h($checklists[48]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
            <dd class="list-item">
              <p class="check-item">配置画像に問題（モアレ・低解像度・ミラー）がないかの確認</p>
              <div class="check">
                <div id="action49" class="front">
                  <div class="triangle triangle49 action49 line49">
                    <select class="triangle-box action49" name="triangles[49]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[49]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action49 line49">
                    <input type="hidden" name="checklists[49]" value="未チェック">
                    <input type="checkbox" name="checklists[49]" value="チェック済" <?= h($checklists[49]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
                <div id="action50" class="back">
                  <div class="triangle triangle50 action50 line50">
                    <select class="triangle-box action50" name="triangles[50]">
                      <option value="未選択"></option>
                      <option value="選択済" <?= h($triangles[50]) === '選択済' ? 'selected' : '' ?>>△</option>
                    </select>
                  </div>
                  <div class="checklist action50 line50">
                    <input type="hidden" name="checklists[50]" value="未チェック">
                    <input type="checkbox" name="checklists[50]" value="チェック済" <?= h($checklists[50]) === 'チェック済' ? 'checked' : '' ?>>
                  </div>
                </div>
              </div>
            </dd>
          </dl>
        </div>

        <div class="other">
          <div class="item">
            <div class="comment">
              <p class="label">備考</p>
              <p>（※C特別検査項目の全部または一部を省く指示がある場合はここに指示内容を転記すること）</p>
            </div>
            <textarea id="comment" name="comment"><?= h($work->comment ?? '') ?></textarea>
          </div>
          <dl class="item print-flex-item">
            <dt class="label">
              <label for="kensa_sekinin">検査責任者</label>
            </dt>
            <dd class="box">
              <select id="kensa_sekinin" name="kensa_sekinin">
                <option value="">選択してください</option>
                <?php foreach ($data['kensa_sekinin'] as $name): ?>
                  <option value="<?= h($name); ?>" <?= h($work->kensa_sekinin) === h($name) ? 'selected' : '' ?>><?= h($name); ?></option>
                <?php endforeach; ?>
              </select>
            </dd>
          </dl>
          <dl class="item print-flex-item">
            <dt class="label">
              <label for="kensa_tantou">検査担当者</label>
            </dt>
            <dd class="box">
              <select id="kensa_tantou" class="sign-box select2" name="kensa_tantou[]" multiple>
                <option></option>
                <?php foreach ($data['kensa_tantou'] as $name): ?>
                  <option value="<?= h($name); ?>" <?= h($work->kensa_tantou) === h($name) ? 'selected' : '' ?>><?= h($name); ?></option>
                <?php endforeach; ?>
              </select>
            </dd>
          </dl>
          <dl class="item print-flex-item">
            <dt class="label">
              <label for="sagyou_tantou">作業担当者</label>
            </dt>
            <dd class="box">
              <select id="sagyou_tantou" class="sign-box select2" name="sagyou_tantou[]" multiple>
                <option></option>
                <?php foreach ($data['sagyou_tantou'] as $name): ?>
                  <option value="<?= h($name); ?>" <?= h($work->sagyou_tantou) === h($name) ? 'selected' : '' ?>><?= h($name); ?></option>
                <?php endforeach; ?>
              </select>
            </dd>
          </dl>
          <div class="confirm">
            <label for="confirmBtn" class="submit">
              <input id="confirmBtn" type="checkbox" name="confirmBtn">
              入力内容を確認しました
            </label>
            <input type="hidden" name="session_token" value="<?= $session_token; ?>" />
            <div class="edit-btn-flex">
              <button class="btn" id="submitBtn" name="submitBtn" disabled>登録</button>
              <a class="print disabled-print" href="#" onclick="window.print(); return false;">印刷</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
  <script>
    //Select2を初期化
    $(function() {
      $('.select2').select2({
        placeholder: "選択してください",
      });
    });
    // javascript用にログイン中のアカウント名をjson形式に変換
    const loginName = <?= json_encode($_SESSION['loginName']); ?>;

    // 検査責任者アカウントでログインしている場合選択可能
    const select = document.getElementById('kensa_sekinin');

    if (loginName !== 'admin') {
      select.classList.add('no-access');
    }
  </script>
  <script src="./assets/js/line.js"></script>
  <script src="./assets/js/confirm.js"></script>
</body>

</html>