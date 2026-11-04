<script setup>
import SiteLayout from '../layouts/SiteLayout.vue';
</script>

<template>
    <SiteLayout>
        <template #default>
<div class="post">
	<h2 class="title"><a href="#"><b>Connect With Me!</b></a></h2>

	<div class="entry">
		<h3><br></h3>
		<p>Please feel free to connect through any of these mediums! </p>
		<p><strong>Work: <br>
		</strong> Cochlear Implant Laboratory, ECSN 4.418<br>University of Texas at Dallas<br>800 West Campbell Road, Richardson, TX-750803021<br>
		<strong>Mobile:</strong> <u> +6823655363</u>	<br>
		<strong>Email:</strong> <u><a href="mailto:nursadul.mamun@utdallas.edu"> nursadul.mamun@utdallas.edu</a></u>	<br><br>

		</p><h3 class="title"><a :href="$url('/2020-Nursadul-Mamun-Resume-Internship-2020.pdf')"><u> <strong>Download My Resume (short)</strong></u></a></h3><br>
		<h3 class="title"><a :href="$url('/2020-Resume-Nursadul-Mamun-July-2020.pdf')"><u> <strong>Download My Full CV </strong></u></a></h3>


	
	</div>
</div>

<div class="post">
	<br>


	<div class="contact-icons">
		<a href="mailto:nxm175730@utdallas.edu"><img :src="$url('/images/email_logo.jpg')" alt="Email"></a>
		<a href="https://www.linkedin.com/in/nursadul-mamun-85980752/"><img :src="$url('/images/LinkedIn_Logo.png')" alt="LinkedIn"></a>
		<a href="https://www.facebook.com/nursadul.mamun"><img :src="$url('/images/facebook_icon.svg')" alt="Facebook"></a>
	</div>


</div>


<div style="clear: both;"> </div>
        </template>
        <template #sidebar>
<ul>
	<li>
		


		<img class="left" :src="$url('/images/Meet.png')" width="250" height="350" alt="">
		<div id="search">


		</div>
		<div style="clear: both;"> </div>


</li></ul>
        </template>
    </SiteLayout>
</template>
