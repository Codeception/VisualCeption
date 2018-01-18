<!DOCTYPE html>
<html>
    <head>
        <title>VisualCeption Report</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <link href="https://cdn.rawgit.com/noelboss/featherlight/1.7.12/release/featherlight.min.css" type="text/css" rel="stylesheet" />
        <style>
            img.img-trimmed{max-height: 400px;}
            img.img-trimmed.featherlight-inner{max-height: none !important;}
        </style>
        <?php
            function convertPNGtoJPEG($fileNameIn) {
                $backgroundImagick = new \Imagick($fileNameIn);
                $imagick = new \Imagick();
                $imagick->setCompressionQuality(75);
                $imagick->newPseudoImage($backgroundImagick->getImageWidth(), $backgroundImagick->getImageHeight(), 'canvas:white');
                $imagick->compositeImage($backgroundImagick, \Imagick::COMPOSITE_ATOP, 0, 0);
                $imagick->setFormat('jpg');
                $imagick->stripImage();
                $tmpJpg = $imagick->getImageBlob();
                $backgroundImagick->clear();
                $backgroundImagick->destroy();
                $imagick->clear();
                $imagick->destroy();
                return $tmpJpg;
            }
        ?>
    </head>
    <body>
        <div class="container-fluid">
            <?php $idx=1; ?>
            <?php foreach ($failedTests as $name => $failedTest): ?>
                <div class="row">
                    <div class="col-md-12">
                        <p class="text-center">
                            <h3 class="text-center text-danger"><?php echo $name ?></h3>
                        </p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Current Image
                            </div>
                            <div class="panel-body">
                                <a href="#" data-featherlight='#current-<?= $idx ?>'>
                                    <img id="current-<?= $idx ?>" class="img-responsive img-thumbnail center-block img-trimmed" src='data:image/png;base64,<?php echo base64_encode(convertPNGtoJPEG($failedTest->getCurrentImage())); ?>' />
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                Expected Image
                            </div>
                            <div class="panel-body">
                                <a href="#" data-featherlight='#expected-<?= $idx ?>'>
                                    <img id="expected-<?= $idx ?>" class="img-responsive img-thumbnail center-block img-trimmed" src='data:image/png;base64,<?php echo base64_encode(convertPNGtoJPEG($failedTest->getExpectedImage())); ?>' />
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                Deviation Image
                            </div>
                            <div class="panel-body">
                                <a href="#" data-featherlight='#deviation-<?= $idx ?>'>
                                    <img id="deviation-<?= $idx ?>" class="img-responsive img-thumbnail center-block img-trimmed" src='data:image/png;base64,<?php echo base64_encode(convertPNGtoJPEG($failedTest->getDeviationImage())); ?>' />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <?php $idx++; ?>
            <?php endforeach; ?>
        </div>
​
        <script src="https://code.jquery.com/jquery-latest.js"></script>
        <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script> -->
        <script src="https://cdn.rawgit.com/noelboss/featherlight/1.7.12/release/featherlight.min.js" type="text/javascript" charset="utf-8"></script>        
    </body>
</html>
