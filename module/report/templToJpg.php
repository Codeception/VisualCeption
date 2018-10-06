<!DOCTYPE html>
<html>
	<head>
		<title>VisualCeption Report</title>
	</head>
	<body>

		<img src='data:image/png;base64,<?php echo base64_encode(file_get_contents(__DIR__.'/img/visualception.png')); ?>' />

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
	<?php foreach ($failedTests as $name => $failedTest): ?>
	
		<h1 style="text-align: center;"><?php echo $name ?></h1>
		
		<div style="display:table; border-spacing:3px; width:100%;">
		
			<div class="expectedimage" style="display:table-cell; border:solid 1px green; text-align: center;">
				Expected Image <br />
				<img style="max-width: 100%;" src='data:image/jpg;base64,<?php echo base64_encode(convertPNGtoJPEG($failedTest->getExpectedImage())); ?>' />
			</div>

			<div class="deviationimage" style="display:table-cell; border:solid 1px green; text-align: center;">
				Deviation Image <br />
				<img style="max-width: 100%;" src='data:image/jpg;base64,<?php echo base64_encode(convertPNGtoJPEG($failedTest->getDeviationImage())); ?>' />
			</div>

			<div class="currentimage" style="display:table-cell; border:solid 1px green; text-align: center;">
				Current Image <br />
				<img style="max-width: 100%;" src='data:image/jpg;base64,<?php echo base64_encode(convertPNGtoJPEG($failedTest->getCurrentImage())); ?>' />
			</div>
			
		</div>
		
		<hr>
		
	<?php endforeach; ?>
	
	<script type="text/javascript">
		images = document.querySelectorAll('div > img')
		for (var i = 0; i < images.length; i++) {
			images[i].addEventListener('click', function(event) {
				var link = this.getAttribute('src');
				var myWindow = window.open(link);
				myWindow.document.write("<img src=" + link + ">");
			});
		}
	</script>

	</body>
</html>
