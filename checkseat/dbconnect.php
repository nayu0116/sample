<?php

/**
 * DB接続関数
 * @return PDO
 */
function dbConnect()
{
  define('DB_HOST', 'mysql:dbname=checkseat;host=localhost;charset=utf8');
  define('DB_USER', 'root');
  define('DB_PASSWORD', 'root');

  try {
    $pdo = new PDO(DB_HOST, DB_USER, DB_PASSWORD, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 例外が発生した際にスローする
      PDO::ATTR_EMULATE_PREPARES => false, // （★SQLインジェクション対策）
    ]);
    return $pdo;
  } catch (PDOException $e) {
    echo '接続失敗: ' . $e->getMessage();
    exit();
  }
}
