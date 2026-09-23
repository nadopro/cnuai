<?php
$rgbValues = [];

for ($i = 0; $i <= 0xF0; $i += 0x10) {
    $rgbValues[] = $i;
    $rgbValues[] = $i + 0x08;
}

$colorsPerRow = 3;
$count = 0;
?>

<div class="container-fluid py-3">
    <h2 class="mb-3">RGB 색상표</h2>

    <div class="alert alert-secondary">
        RGB 각 값의 끝자리가 <strong>0 또는 8</strong>인 색상을 표시합니다.
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <tbody>

            <?php
            foreach ($rgbValues as $r) {
                foreach ($rgbValues as $g) {
                    foreach ($rgbValues as $b) {

                        $hex = sprintf("#%02X%02X%02X", $r, $g, $b);

                        if ($count % $colorsPerRow == 0) {
                            echo "<tr>";
                        }

                        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;
                        $textColor = ($brightness < 128) ? "#FFFFFF" : "#000000";

                        echo "<td class='text-nowrap'>" . $hex . "</td>";
                        echo "<td class='text-center text-nowrap' "
                           . "style='background-color:" . $hex . "; "
                           . "color:" . $textColor . "; "
                           . "min-width:180px;'>"
                           . "" . $hex
                           . "</td>";

                        $count++;

                        if ($count % $colorsPerRow == 0) {
                            echo "</tr>";
                        }
                    }
                }
            }

            $hex = "#FFFFFF";

            if ($count % $colorsPerRow == 0) {
                echo "<tr>";
            }

            echo "<td class='text-nowrap'>" . $hex . "</td>";
            echo "<td class='text-center text-nowrap' "
               . "style='background-color:#FFFFFF; color:#000000; min-width:180px;'>"
               . " #FFFFFF"
               . "</td>";

            $count++;

            $remain = $count % $colorsPerRow;

            if ($remain != 0) {
                for ($i = $remain; $i < $colorsPerRow; $i++) {
                    echo "<td></td><td></td>";
                }
                echo "</tr>";
            }
            ?>

            </tbody>
        </table>
    </div>
</div>
