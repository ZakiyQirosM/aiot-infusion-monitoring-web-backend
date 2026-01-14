<?php

if (!function_exists('checkLogin')) {
    function checkLogin()
    {
        if (!session()->has('pegawai')) {
            return redirect('/infusee')->with('error', 'Silakan login terlebih dahulu');
        }
    }
}

if (!function_exists('maskNama')) {
    function maskNama($nama) {
        $kata = explode(' ', $nama);
        $hasil = [];

        foreach ($kata as $i => $k) {
            $len = strlen($k);

            // Kata pertama dan kedua
            if ($i == 0 || $i == 1) {
                if ($len > 2) {
                    $hasil[] = substr($k, 0, $len - 2) . '**';
                } else {
                    $hasil[] = str_repeat('*', $len);
                }
            }
            // Kata ketiga
            elseif ($i == 2) {
                if ($len > 2) {
                    $hasil[] = substr($k, 0, 2) . str_repeat('*', $len - 2);
                } else {
                    $hasil[] = str_repeat('*', $len);
                }
            }
            // Kata ke-4 dst
            else {
                $hasil[] = str_repeat('*', $len);
            }
        }

        return implode(' ', $hasil);
    }
}

