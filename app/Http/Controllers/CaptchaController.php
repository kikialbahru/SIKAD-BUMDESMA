<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CaptchaController extends Controller
{
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function image(Request $request)
    {
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }

        $request->session()->put('captcha_code', $code);

        if (! function_exists('imagecreatetruecolor')) {
            return $this->svg($code);
        }

        $width = 170;
        $height = 55;
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 242, 248, 252);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($image, random_int(170, 220), random_int(190, 230), random_int(200, 240));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $color);
        }

        for ($i = 0; $i < 120; $i++) {
            $color = imagecolorallocate($image, random_int(120, 220), random_int(140, 220), random_int(160, 230));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $color);
        }

        $x = 14;
        foreach (str_split($code) as $char) {
            $color = imagecolorallocate($image, random_int(10, 70), random_int(70, 120), random_int(110, 170));
            $y = random_int(8, 20);
            $glyph = imagecreatetruecolor(12, 20);
            $transparent = imagecolorallocate($glyph, 0, 0, 0);
            imagecolortransparent($glyph, $transparent);
            imagefill($glyph, 0, 0, $transparent);
            imagestring($glyph, 5, 1, 1, $char, $color);
            imagecopyresampled($image, $glyph, $x, $y, 0, 0, 26, 38, 12, 20);
            imagedestroy($glyph);
            $x += 30;
        }

        ob_start();
        imagepng($image, null, 6);
        $content = ob_get_clean();
        imagedestroy($image);

        return response($content, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function svg(string $code)
    {
        $width = 170;
        $height = 55;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
        $svg .= '<rect width="100%" height="100%" fill="#f2f8fc"/>';

        for ($i = 0; $i < 6; $i++) {
            $svg .= '<line x1="'.random_int(0, $width).'" y1="'.random_int(0, $height)
                .'" x2="'.random_int(0, $width).'" y2="'.random_int(0, $height)
                .'" stroke="rgb('.random_int(170, 220).','.random_int(190, 230).','.random_int(200, 240).')" stroke-width="1.5" opacity="0.7"/>';
        }

        for ($i = 0; $i < 120; $i++) {
            $svg .= '<circle cx="'.random_int(0, $width).'" cy="'.random_int(0, $height)
                .'" r="1" fill="rgb('.random_int(120, 220).','.random_int(140, 220).','.random_int(160, 230).')"/>';
        }

        $x = 18;
        foreach (str_split($code) as $char) {
            $y = random_int(28, 38);
            $rotate = random_int(-18, 18);
            $svg .= '<text x="'.$x.'" y="'.$y.'" font-family="monospace" font-size="30" font-weight="700"'
                .' fill="rgb('.random_int(10, 70).','.random_int(70, 120).','.random_int(110, 170).')"'
                .' transform="rotate('.$rotate.' '.$x.' '.$y.')">'.htmlspecialchars($char, ENT_XML1).'</text>';
            $x += 30;
        }

        $svg .= '</svg>';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function matches(Request $request, ?string $input): bool
    {
        $expected = $request->session()->pull('captcha_code');

        return is_string($expected)
            && is_string($input)
            && hash_equals(Str::upper($expected), Str::upper(trim($input)));
    }
}
