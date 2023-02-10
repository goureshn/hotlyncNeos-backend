@extends('layout.layout')
@section('content')
	<section style="float:left;width:1159px;height:100%">
		<link href="/css/admin_setting.css" rel="stylesheet">
		<div id="setting_div">			
			<div id="setting_sidebar" class="sidebar_back">
				@include('backoffice.wizard.sidebar')
			</div>
			<div id="setting_content">
				<div id="setting_sub_content">
				@section('setting_nav')
					<div class="setting_menu_group" style="margin-left:55px;width:90%">
						@if($errors->any())
							<h4>{{$errors->first()}}</h4>
						@endif
						<?php 
							$selected = array();
							for( $i = 0; $i < 10; $i++ )
							{
								if( $i < $step )
									array_push($selected, 'selector');
								else
									array_push($selected, '');
							}
								
						?>			
					
						<div id="departfunc" class="settingmenu {{$selected['0']}}">
							<span>					
								Department function
							</span>		
						</div>						
						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['0']}}"></span>	
						</div>						
						
						<div id="location_group" class="settingmenu {{$selected['1']}}">
							<span>						
								Location groups
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['1']}}"></span>	
						</div>						
						<div id="escalation" class="settingmenu {{$selected['2']}}">
							<span>						
								Escalation
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['2']}}"></span>	
						</div>						
						<div id="task" class="settingmenu {{$selected['3']}}">
							<span>						
								Task
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['3']}}"></span>	
						</div>						
						
						<div id="minibar" class="settingmenu {{$selected['4']}}">
							<span>						
								Minibar
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['4']}}"></span>	
						</div>		
						
						<div id="housekeeping" class="settingmenu {{$selected['5']}}">
							<span>						
								House keeping
							</span>
						</div>
						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['5']}}"></span>	
						</div>		
						
						<div id="device" class="settingmenu {{$selected['6']}}">
							<span>						
								Device
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['6']}}"></span>	
						</div>		
						
						<div id="shifts" class="settingmenu {{$selected['7']}}">
							<span>						
								Shifts
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['7']}}"></span>	
						</div>		
						
						<div id="alara" class="settingmenu {{$selected['8']}}">
							<span>						
								Alarms
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