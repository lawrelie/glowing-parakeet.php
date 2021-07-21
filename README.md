# glowing-parakeet.php
CMS (PHP>=8.0)

```php
include 'path/to/glowing-parakeet.php';
$parakeet = new Lawrelie\GlowingParakeet\Parakeet(
    [
        'index' => $index,
        'timezone' => \timezone_open('Asia/Tokyo'),
        'url' => 'https://' . $_SERVER['SERVER_NAME'],
    ],
);
```

## 設定 `$contents`

*イテレータ*、又はイテレータを返す*ＰＨＰファイル*。

```php
return ['id' => 'string/like/path'];
```

### `$index`
