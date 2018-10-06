<!DOCTYPE html>
<html>
	<head>
		<title>VisualCeption Report</title>
	</head>
	<body>

		<img src='data:image/png;base64,<?php echo base64_encode(file_get_contents(__DIR__.'/img/visualception.png')); ?>' />

	<?php foreach ($failedTests as $name => $failedTest): ?>
	
		<h1 style="text-align: center;"><?php echo $name ?></h1>
		
		<div style="display:table; border-spacing:3px; width:100%;">
		
			<div class="deviationimage" style="display:table-cell; border:solid 1px green; text-align: center;">
				Deviation Image <br />
				<img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($failedTest->getDeviationImage())); ?>' />
			</div>

			<div class="expectedimage" style="display:table-cell; border:solid 1px green; text-align: center;">
				Expected Image <br />
				<img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($failedTest->getExpectedImage())); ?>' />
			</div>

			<div class="currentimage" style="display:table-cell; border:solid 1px green; text-align: center;">
				Current Image <br />
				<img src='data:image/png;base64,<?php echo base64_encode(file_get_contents($failedTest->getCurrentImage())); ?>' />
			</div>
			
		</div>
		
		<hr>
		
	<?php endforeach; ?>
	
	<script type="text/javascript">
		images = document.querySelectorAll('div > img')
		for (var i = 0; i < images.length; i++) {
			images[i].addEventListener('click', function(event) {
			window.open(this.getAttribute('src'));
			});
		}
	</script>

	</body>
</html>