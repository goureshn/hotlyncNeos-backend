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
					<div class="setting_menu_group" style="margin-left:155px;">
						@if($errors->any())
							<h4>{{$errors->first()}}</h4>
						@endif
						<?php 
							$path = 'backoffice/property/wizard';
							$selected = array();
							for( $i = 0; $i < 13; $i++ )
							{
								if( $i < $step )
									array_push($selected, 'selector');
								else
									array_push($selected, '');
							}
								
						?>			
					
						<div id="section" class="settingmenu {{$selected['0']}}">
							<span>					
								Section
							</span>		
						</div>					
						<div class="settingmenu_div">
							<span class="linediv {{$selected['0']}}"></span>	
						</div>						
						
						<div id="admin" class="settingmenu {{$selected['1']}}">
							<span>						
								Admin Extn		
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['1']}}"></span>	
						</div>		
						
						<div id="guest" class="settingmenu {{$selected['2']}}">
							<span>						
								Guest Extn
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['2']}}"></span>	
						</div>		
						
						<div id="carrier" class="settingmenu {{$selected['3']}}">
							<span>						
								Carrier
							</span>
						</div>					
						<div class="settingmenu_div">
							<span class="linediv {{$selected['3']}}"></span>	
						</div>		
						
						<div id="destination" class="settingmenu {{$selected['4']}}">
							<span>						
								Destination
							</span>
						</div>	
						<div class="settingmenu_div">
							<span class="linediv {{$selected['4']}}"></span>	
						</div>		
						
						<div id="carrier_group" class="settingmenu {{$selected['5']}}">
							<span>						
								Carrier Group
							</span>
						</div>						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['5']}}"></span>	
						</div>			
						
						<div id="carrier_charge" class="settingmenu {{$selected['6']}}">
							<span>						
								Carrier Charge
							</span>
						</div>						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['6']}}"></span>	
						</div>		
						
						<div id="property_charge" class="settingmenu {{$selected['7']}}">
							<span>						
								Property Charge
							</span>
						</div>						
						<div class="settingmenu_div">
							<span class="linediv {{$selected['7']}}"></span>	
						</div>		
						
						<div id="tax" class="settingmenu {{$selected['8']}}">
							<span>						
								Tax
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['8']}}"></span>	
						</div>			
						
						<div id="allowance" class="settingmenu {{$selected['9']}}">
							<span>						
								Allowance
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['9']}}"></span>	
						</div>	
						
						<div id="time_slab" class="settingmenu {{$selected['10']}}">
							<span>						
								Time Slab
							</span>
						</div>
						<div class="settingmenu_div">
							<span class="linediv {{$selected['10']}}"></span>	
						</div>			
						
						<div id="rate_map" class="settingmenu {{$selected['11']}}">
							<span>						
								Rate Map1
							</span>
						</div>							
						<div class="settingmenu_div">
							<span class="linediv {{$selected['11']}}"></span>	
						</div>			
						
						<div id="rate_map" class="settingmenu {{$selected['12']}}">
							<span>						
								Rate Map2
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