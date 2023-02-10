@extends('layout.layout')
@section('content')
	<section style="float:left;width:100%;height:100%">
		<link href="/css/admin_setting.css" rel="stylesheet">
		<div id="setting_div">
			
			<div id="setting_sidebar" class="sidebar_back">
				@include('backoffice.wizard.sidebar')
			</div>
			<div id="setting_content">
				<div id="setting_sub_content">
				@section('setting_nav')
					<div class="setting_menu_group" style="margin-left:25%;width:60%">
						@if($errors->any())
							<h4>{{$errors->first()}}</h4>
						@endif
						<?php 
							$path = 'backoffice/property/wizard';
							$selected = array();
							for( $i = 0; $i < 4; $i++ )
							{
								if( $i < $step )
									array_push($selected, 'selector');
								else
									array_push($selected, '');
							}
								
						?>			
					
						<div id="department" class="settingmenu {{$selected['0']}}" style="font-size:10px;">
							<span style="font-size:10px;">					
								Department
							</span>		
						</div>						
						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['0']}}"></span>	
						</div>						
						
						<div id="common" class="settingmenu {{$selected['1']}}">
							<span>						
								Common Area
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['1']}}"></span>	
						</div>						
						<div id="admin" class="settingmenu {{$selected['2']}}">
							<span>						
								Admin Area
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['2']}}"></span>	
						</div>						
						<div id="outdoor" class="settingmenu {{$selected['3']}}">
							<span>						
								Outdoor Area
							</span>
						</div>						
					</div>		
					<div style="clear:both;margin-top:150px;positive:relative">
						@yield('setting_content')								
					</div>	
				@show	
				
				</div>
			
			</div>	
		</div>	
	</section>
	
@stop