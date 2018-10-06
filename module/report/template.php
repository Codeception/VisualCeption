<!DOCTYPE html>
<html>
    <head>
        <title>VisualCeption Report</title>
    </head>
    <body>

		<img src='data:image/png;base64,<?php echo base64_encode(file_get_contents(__DIR__.'/img/visualception.png')); ?>' />

        <?php foreach ($failedTests as $name => $failedTest): ?>

            <h1><?php echo $name ?></h1>

            <div class="deviationimage">
                Deviation Image <br />
                <img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($failedTest->getDeviationImage())); ?>' />
            </div>

            <div class="expectedimage">
                Expected Image <br />
                <img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($failedTest->getExpectedImage())); ?>' />
            </div>

            <div class="currentimage">
                Current Image <br />
                <img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($failedTest->getCurrentImage())); ?>' />
            </div>

            <hr>


        <?php endforeach; ?>

    </body>
</html>