<script setup>
import SiteLayout from '../layouts/SiteLayout.vue';
</script>

<template>
    <SiteLayout>
        <template #default>
<div class="post">
	<h2 class="title"><a href="#"><b>EXPERIENCE</b></a></h2>

	<div class="entry">
		<h3><br></h3>
		<h3 class="title"><a href="#">Research Assistant </a></h3>
		<p><strong><a href="https://crss.utdallas.edu/"><u>CENTER FOR ROBUST SPEECH SYSTEMS</u></a></strong> (August 2018-Present)</p>
		<p>- Supervise production of 30 CCi-Mobile research platforms, to be shipped to other research labs.<br>- Make advancements to the existing Verilog code on the <a href="https://www.utdallas.edu/~hussnain.ali/CCIMobile.html"><u>CCi-Mobile Platform</u></a> for testing cochlear<br>    implants.<br> - Work towards a better and compact revision of the CCi-Mobile platform allowing more than one    algorithms existing on the FPGA, with capabilities to switch between the strategies.<br>- Incorporate latest robust technology along with wireless networking strategies for non-clinical trials</p>
		<p><strong>Skills acquired: </strong> VHDL, Verilog,C, MATLAB,EAGLE,Testing and Documentation,Product Management</p>
	</div>
</div>
<div class="post post-alt">

	<h3 class="title"><a href="#">Embedded System Control Engineer (Intern)</a></h3>
	<p><strong><a href="http://www.utdallas.edu/~rdg130030/locolab/index.html"><u>LOCOMOTOR SYSTEM CONTROL LABORATORY</u></a></strong> (May 2018-August 2018) </p>
	<p><strong>Supervisor:</strong><a href="http://www.utdallas.edu/~rdg130030/index.html"><u> Dr. Robert Gregg</u> <br> </a> - Program electrical system with controlled feedback loop for Prosthetic legs and Exo-Skeletons. -Interface sensors,CAN transeiver with myRIO and the TI LaunchPad C2000 using LabVIEW and CCS. -Design actuator system for precise control of the Elmo Twitter Motors using Bluetooth, SPI and I2C. -Design custom-made PCB for integration of sensors and motor drivers with the microcontroller</p>
	<div class="entry">
		<p><strong>Skills acquired: </strong>Automotive Networks,C and Java GUI programming,Wireless and wired interfacing of circuits,KiCAD,Soldering,Debugging using JTAG </p>

	</div>
</div>
<div class="post">

	<h3 class="title"><a href="#">Intern-Engineering Design &amp; Implementation</a></h3>
	<p><strong><a href="http://www.techedventures.com/"><u>TECH EDVENTURES</u></a></strong> (May 2017-April-2018)</p>
	<p>-Implement Pilot Drone Design<br>- Embedded Controller Hardware Design and Robotic Car Design using Arduino<br>- C++ and Verilog on OEM boards and Genuine MKR1000 <br>- Ensuring prevention of Technical issues, Testing machines and measurement systems </p>
	<div class="entry">
		<p><strong>Skills acquired:</strong> Circuit Design,ARM Cortex Micro-controllers,Wearable devices,Python and C++</p>

	</div>
</div>
<div class="post">

	<h3 class="title"><a href="#">Hardware Design Intern</a></h3>
	<p><strong><a href="https://www.indiamart.com/vardhman-edutech/aboutus.html"><u>VARDHAMAN EDUTECH PVT LTD.</u></a></strong> (May 2015-July 2016)</p>
	<p>- Verilog/ VHDL coding for Power-Optimization ofDigital Circuits and Performance optimization of <br>   
	 Analog circuits.<br>- Research mechanisms to reduce Power Dissipation at transistor level<br>- Burn on FPGA kit and compare Dynamic Power utilization <br>- Testing, Debugging, Troubleshooting and Documentation of RTL designs </p>
	<div class="entry">
		<p><strong>Skills acquired:</strong>Power Optimization techniques, VHDL, Cadence, Synopsys, FPGA/CPLD implementation      </p>
		<p class="links"><a href="#"></a></p>
	</div>
</div>
        </template>
        <template #sidebar>
<ul>
	<li>
		
		<br><br>
		<img class="left" :src="$url('/images/CRSS-UTD.jpg')" width="300" height="310" alt="">
		<img class="center" :src="$url('/images/UTDleg_full.jpg')" width="300" height="300" alt="">
		<div id="search">

			<img class="center" :src="$url('/images/teched.jpg')" width="250" height="230" alt="">
			<img class="center" :src="$url('/images/vardhman.jpg')" width="300" height="300" alt="">
		</div>
		<div style="clear: both;"> </div>


</li></ul>
        </template>
    </SiteLayout>
</template>
