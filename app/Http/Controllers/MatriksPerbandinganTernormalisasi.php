<?php

namespace App\Http\Controllers;

use App\Models\ElectreAlternative;
use Illuminate\Http\Request;
use App\Models\ElectreCriteria;
use App\Models\ElectreEvaluation;

class ElektreController extends Controller
{
    public function index() {
        return view('elektre');
    }
    
    public function getAlternatives() {
        $alternatives = ElectreAlternative::all();
        return response()->json(['alternatives' => $alternatives]);
    }

    public function getCriterias() {
        $criterias = ElectreCriteria::all();
        return response()->json(['criterias' => $criterias]);
    }

    public function result() {
        $n = ElectreCriteria::count();

        $evaluations = ElectreEvaluation::orderBy('id_alternative')
            ->orderBy('id_criteria')
            ->get();

        $X = [];
        $alternative = '';
        $m = 0;

        foreach ($evaluations as $evaluation) {
            if ($evaluation->id_alternative != $alternative) {
                $X[$evaluation->id_alternative] = [];
                $alternative = $evaluation->id_alternative;
                $m++;
            }
            $X[$evaluation->id_alternative][$evaluation->id_criteria] = $evaluation->value;
        }

        $x_rata = [];

        foreach ($X as $i => $x) {
            foreach ($x as $j => $value) {
                $x_rata[$j] = (isset($x_rata[$j]) ? $x_rata[$j] : 0) + pow($value, 2);
            }
        }

        for ($j = 1; $j <= $n; $j++) {
            $x_rata[$j] = sqrt($x_rata[$j]);
        }
        
        $R = [];
        $alternative = '';

        foreach ($X as $i => $x) {
            if ($alternative != $i) {
                $alternative = $i;
                $R[$i] = [];
            }
            foreach ($x as $j => $value) {
                $R[$i][$j] = $value / $x_rata[$j];
            }
        }

        $criteria = ElectreCriteria::orderBy('id_criteria')
            ->pluck('weight', 'id_criteria')
            ->toArray();

        $V = [];

        foreach ($R as $i => $r) {
            $V[$i] = [];

            foreach ($r as $j => $value) {
                $V[$i][$j] = $criteria[$j] * $value;
            }
        }

        $c = [];
        $d = [];

        for ($k = 1; $k <= $m; $k++) {
            $c[$k] = [];
            $d[$k] = [];

            for ($l = 1; $l <= $m; $l++) {
                if ($k !== $l) {
                    $c[$k][$l] = [];
                    $d[$k][$l] = [];

                    for ($j = 1; $j <= $n; $j++) {
                        if ($V[$k][$j] >= $V[$l][$j]) {
                            array_push($c[$k][$l], $j);
                        } else {
                            array_push($d[$k][$l], $j);
                        }
                    }
                } else {
                    $c[$k][$l] = ['-'];
                    $d[$k][$l] = ['-'];
                }
            }
        }

        $sigma_c = 0;
        $sigma_d = 0;

        foreach ($c as $cl) {
            foreach ($cl as $l => $value) {
                foreach ($criteria as $j => $weight) {
                    if (in_array($j, $value)) {
                        $sigma_c += $weight;
                    }
                }
            }
        }

        for ($k = 1; $k <= $m; $k++) {
            for ($l = 1; $l <= $m; $l++) {
                if ($k !== $l) {
                    $max_d = 0;
                    $max_j = 0;

                    if (count($d[$k][$l])) {
                        for ($j = 0; $j < count($d[$k][$l]); $j++) {
                            $current_j = $d[$k][$l][$j];
                            $difference = abs($V[$k][$current_j] - $V[$l][$current_j]);

                            if ($max_d < $difference) {
                                $max_d = $difference;
                            }
                        }
                    }

                    for ($j = 1; $j <= $n; $j++) {
                        $difference = abs($V[$k][$j] - $V[$l][$j]);

                        if ($max_j < $difference) {
                            $max_j = $difference;
                        }
                    }

                    $D[$k][$l] = $max_d / $max_j;
                    $sigma_d += $D[$k][$l];
                }
            }
        }

        $threshold_c = $sigma_c / ($m * ($m - 1));
        $threshold_d = $sigma_d / ($m * ($m - 1));

        $F = [];
        $G = [];

        foreach ($c as $k => $cl) {
            $F[$k] = [];

            foreach ($cl as $l => $value) {
                if (in_array('-', $value)) {
                    $F[$k][$l] = '-';
                } else {
                    $total = 0;
                    foreach ($criteria as $j => $weight) {
                        if (in_array($j, $value)) {
                            $total += $weight;
                        }
                    }

                    $F[$k][$l] = ($total >= $threshold_c) ? 1 : 0;
                }
            }
        }

        for ($k = 1; $k <= $m; $k++) {
            for ($l = 1; $l <= $m; $l++) {
                if ($k !== $l) {
                    $max_d = 0;
                    $max_j = 0;
                    $total = 0; // Reset total setiap kali ganti baris atau kolom

                    if (count($d[$k][$l])) {
                        for ($j = 0; $j < count($d[$k][$l]); $j++) {
                            $current_j = $d[$k][$l][$j];
                            $difference = abs($V[$k][$current_j] - $V[$l][$current_j]);

                            if ($max_d < $difference) {
                                $max_d = $difference;
                            }
                        }
                    }

                    for ($j = 1; $j <= $n; $j++) {
                        $difference = abs($V[$k][$j] - $V[$l][$j]);

                        if ($max_j < $difference) {
                            $max_j = $difference;
                        }
                    }

                    $D[$k][$l] = $max_d / $max_j;
                    $total += $D[$k][$l]; // Akumulasi total

                    // Membandingkan dengan treshold_d dan menambahkan 1 atau 0 ke $G
                    $G[$k][$l] = ($total > $threshold_d) ? 1 : 0;
                } else {
                    $G[$k][$l] = '-';
                }
            }
        }

        $E = [];

        foreach ($F as $k => $sl) {
            $E[$k] = [];

            foreach ($sl as $l => $value) {
                if ($value === '-') {
                    $E[$k][$l] = '-';
                } else {
                    $E[$k][$l] = $F[$k][$l] * $G[$k][$l];
                }
            }
        }


        return response()->json([
            'n' => $n,
            'X' => $X,
            'm' => $m,
            'x_rata' => $x_rata,
            'R' => $R,
            'criteria' => $criteria,
            'V' => $V,
            'c' => $c,
            'd' => $d,
            'threshold_c' => $threshold_c,
            'threshold_d' => $threshold_d,
            'F' => $F,
            'G' => $G,
            'E' => $E,
        ]);
    }
}