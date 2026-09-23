<?php
$bootstrapColors = [
    ["name" => "primary",   "class" => "bg-primary",   "text" => "text-white"],
    ["name" => "secondary", "class" => "bg-secondary", "text" => "text-white"],
    ["name" => "success",   "class" => "bg-success",   "text" => "text-white"],
    ["name" => "danger",    "class" => "bg-danger",    "text" => "text-white"],
    ["name" => "warning",   "class" => "bg-warning",   "text" => "text-dark"],
    ["name" => "info",      "class" => "bg-info",      "text" => "text-dark"],
    ["name" => "light",     "class" => "bg-light",     "text" => "text-dark"],
    ["name" => "dark",      "class" => "bg-dark",      "text" => "text-white"]
];

$colorsPerRow = 3;
$count = 0;
?>

<div class="container-fluid py-3">
    <h2 class="mb-3">Bootstrap 5 색상표</h2>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <tbody>

            <?php foreach ($bootstrapColors as $color) { ?>

                <?php
                if ($count % $colorsPerRow == 0) {
                    echo "<tr>";
                }
                ?>

                <td class="text-nowrap">
                    <?php echo $color["name"]; ?>
                </td>

                <td class="<?php echo $color["class"] . " " . $color["text"]; ?> text-center text-nowrap"
                    style="min-width:180px;">
                    배경색 <?php echo $color["name"]; ?>
                </td>

                <?php
                $count++;

                if ($count % $colorsPerRow == 0) {
                    echo "</tr>";
                }
                ?>

            <?php } ?>

            <?php
            // 마지막 줄이 3개로 채워지지 않은 경우 빈 칸 추가
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
