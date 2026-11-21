<?php
/* Copyright (C) 2026       Kreativität Works            <mail@kreativitat.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    krea2factorauth/core/tpl/login.tpl.php
 * \ingroup krea2factorauth
 * \brief   Login template wrapper that removes the logo block from Dolibarr's login page.
 */

ob_start();
include DOL_DOCUMENT_ROOT.'/core/tpl/login.tpl.php';
$krea2factorauthloginhtml = ob_get_clean();

$krea2factorauthloginwithoutlogo = preg_replace(
	'/\s*<div id="login_left">\s*<img[^>]*\sid="img_logo"[^>]*>\s*<\/div>\s*<br>\s*/',
	"\n",
	$krea2factorauthloginhtml,
	1
);

$krea2factorauthloginoutput = is_string($krea2factorauthloginwithoutlogo) ? $krea2factorauthloginwithoutlogo : $krea2factorauthloginhtml;

if (strpos($krea2factorauthloginoutput, 'data-krea2factorauth-google-login-row="1"') !== false) {
	$krea2factorauthloginwithoutnativegoogle = preg_replace(
		'/\s*<div class="center" style="margin-top: 20px; margin-bottom: 10px">\s*<input type="hidden" name="beforeoauthloginredirect" id="beforeoauthloginredirect" value="">\s*<a class="alogin" href="#" onclick="[^"]*beforeoauthloginredirect[^"]*">\s*<div class="loginbuttonexternal">.*?<\/div>\s*<\/a>\s*<\/div>\s*/s',
		"\n",
		$krea2factorauthloginoutput,
		1
	);
	if (is_string($krea2factorauthloginwithoutnativegoogle)) {
		$krea2factorauthloginoutput = $krea2factorauthloginwithoutnativegoogle;
	}
}

if (preg_match('/\s*<!-- BEGIN KREA2FACTORAUTH GOOGLE LOGIN -->.*?<!-- END KREA2FACTORAUTH GOOGLE LOGIN -->\s*/s', $krea2factorauthloginoutput, $krea2factorauthgoogleloginmatch)) {
	$krea2factorauthgoogleloginblock = trim($krea2factorauthgoogleloginmatch[0]);
	$krea2factorauthloginwithoutgoogle = str_replace($krea2factorauthgoogleloginmatch[0], "\n", $krea2factorauthloginoutput);
	$krea2factorauthgoogleloginreplacements = 0;
	$krea2factorauthloginwithgoogle = preg_replace_callback(
		'/<div id="login-submit-wrapper">\s*<input\b[^>]*>\s*<\/div>/s',
		function ($matches) use ($krea2factorauthgoogleloginblock) {
			return $matches[0]."\n".$krea2factorauthgoogleloginblock."\n";
		},
		$krea2factorauthloginwithoutgoogle,
		1,
		$krea2factorauthgoogleloginreplacements
	);
	if ($krea2factorauthgoogleloginreplacements > 0 && is_string($krea2factorauthloginwithgoogle)) {
		$krea2factorauthloginoutput = $krea2factorauthloginwithgoogle;
	}
}

print $krea2factorauthloginoutput;
