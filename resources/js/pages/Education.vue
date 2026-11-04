<script setup>
import SiteLayout from '../layouts/SiteLayout.vue';
</script>

<template>
    <SiteLayout>
        <template #default>
<div class="post">

	<h2 class="title"><a href="#"><b>EDUCATION</b></a></h2>

	<div class="entry">
		<img :src="$url('/images/UTD_logo.png')" width="160" vspace="9" align="right">
		<h3 class="title"><a href="#">PhD in Electrical Engineering </a></h3>
		<p><strong><a href="http://www.utdallas.edu"><u>UNIVERSITY OF TEXAS AT DALLAS</u></a></strong> (2018-Present)</p>
		<p><strong>PhD Thesis Advisor:</strong><a href="https://www.utdallas.edu/~jxh052100/"><u> Dr. John Hansen</u> </a></p>
		<p><strong>Current Research:</strong> I joined in the <a href="https://crss.utdallas.edu/CILab/"><u><strong>Cochlear Implant laboratory</strong> </u></a>, UTD as a PhD candidate in Summer, 2018. Being an Electrical Engineer, my research mainly focuses on the Algorithm design and Machine learning for speech enhancement for the development of the<a href="https://crss.utdallas.edu/CILab/CCi-MOBILE.html"><u> CCi-MOBILE</u></a> research platform. My immediate goals are to make a speech enhancement algorithm for the platform to make it robust to the environmental noise and distortions. I am using machine learning tools such as convolutional Neural Network, Reccurrent Neural Network to design speech enhancement algorithm for cochlear Implant recipients. Apart from that I am involved in designing auditory-inspired speaker identification algorithms that able to quantify the speaker identification capabilities of CI users as well as normal hearing individuals.  </p>
		


	</div>
</div>
<div class="post post-alt">
	<img :src="$url('/images/UM_logo.png')" width="160" vspace="9" align="right">
	<h3 class="title"><a href="#">Master of Science in Biomedical Eng.</a></h3>
	<p><strong><a href="https://www.um.edu.my.edu/"><u>University of Malaya, Malaysia</u></a></strong> (2013-2015) </p>
	<p><strong>Master's Thesis Advisor:</strong><a href="https://scholar.google.com/citations?user=6yvrSqwAAAAJ&amp;hl=en&amp;oi=sra"><u> Dr. Muhammad Zilany</u> </a></p>
	<div class="entry">
		<p><strong>Thesis Topic:</strong><a href="https://ieeexplore.ieee.org/abstract/document/7035038"><u> Prediction of Speech Intelligibility Using a Physiologically Based Model of The Auditory System.</u></a> For my Master's research work, I served as <strong> Research Assistant</strong> at Auditory Neuroscience Laboratory. The main focus of my research was to develop a speech intelligibility matric using a physiological based model of the auditory system that can predict human speech intelligibility for listeners with and without hearing loss both in quiet and noisy conditions. The model used in the metric successfully captures most of the nonlinearities observed at the level of the auditory nerve. A well-known orthogonal polynomial measure was used as a features extractor from the auditory neurogram. The predicted scores from the metric showed a good fit with the scores from respective behavioural studies. Apart from this, I was also involved in another line of research work that assessed robust gender classification based on neural responses. This study proposed a gender classification technique using the neural responses of a physiologically-based computational model of the auditory periphery and the performance of the proposed method was evaluated for eight different types of noise. </p>



	</div>
</div>
<div class="post">
	<img :src="$url('/images/CUET_logo.jpg')" width="100" height="100" vspace="9" align="right">
	<h3 class="title"><a href="#">Bachelor in Electrical and Electronic Eng.</a></h3>
	<p><a href="https://www.cuet.ac.bd/"><u><strong>Chittagong University of Engineering and Technology</strong> </u></a>  (2008-2012)</p>
	<p><strong>Bachelor's Thesis Advisor:</strong><a href="https://scholar.google.com.au/citations?user=pxlYgNYAAAAJ&amp;hl=en"><u> Dr. Nur Mohammad</u> </a></p>
	<div class="entry">
		<p><strong>Thesis Topic:</strong><a href="https://ieeexplore.ieee.org/abstract/document/7136008">  <u>Multi-directional solar tracker using low cost photo sensor matrix.</u></a> As my undergradute thesis, a solar tracking system is designed for the orientation of different solar energy receivers, photovoltaic arrays or thermal receivers to maximize the operation of solar energy conversion. This involved the design and implementation of a multi-directional solar tracking system dedicated to use with PV panels. The proposed solar tracking device ensures the maximization of the conversion of solar energy into electricity by properly orienting the PV panel in accordance with the real position of the sun. The dual axis solar tracker moves only in vertical direction with Y and Z-axis movement. It cannot move in horizontal direction with X-axis movement. Our proposed model can move in vertical position with Y-axis movement and in horizontal position with X-axis movement and also move at a point in Z-axis making combination of X and Y-axis movement. The gain of output power with the Multi-directional tracking system is higher when compared with a dual axis tracker. The performance of the proposed solar tracker was experimentally analyzed.
		</p><p class="links"><a href="#"></a></p>
	</div>
</div>
<div style="clear: both;"> </div>
        </template>
        <template #sidebar>
<ul>
	<li>
		<br><br><br><br>
		<img class="left" :src="$url('/images/UTD_pic.jpg')" width="300" height="250" alt="">
		<br><br><br><br><br><br><br><br><br><br>

		<div id="search">
			<img class="left" :src="$url('/images/MS_conv.jpg')" width="300" height="250" alt="">
			<br><br><br><br><br><br><br><br><br><br>

			<img class="left" :src="$url('/images/BS_conv1.jpg')" width="300" height="250" alt="">
		</div>
		<div style="clear: both;"> </div>


</li></ul>
        </template>
    </SiteLayout>
</template>
