<!doctype html>
<html lang="en">

	@include('layout.header')

	<body>

		<div id = "total_div">
			@include('layout.topmenu')
			<div id="content_back">				
				@yield('content')				
			</div>
		</div>

	</body>
</html>