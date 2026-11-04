<script setup>
import SiteLayout from '../layouts/SiteLayout.vue';
</script>

<template>
    <SiteLayout>
        <template #logo>
<h1><a href="#"><center>Nursadul Mamun</center></a></h1>
			<p></p><center>Assistant Professor</center>
			<p> <a href="https://www.cuet.ac.bd/members/365/"><center>Chittagong University of Engineering &amp; Technology </center></a></p>
        </template>
        <template #default>
<div class="post">

	<h2 class="title"><a href="#">HELLO!</a></h2>
	
	<p><img :src="$url('/images/Mamun_4.jpg')" width="290" height="240" alt=""></p>

	
	<div class="entry">
		<h3 class="title"><a href="#"> About Me</a></h3>


		<p>I am an  <strong>Electrical Engineer </strong>, passionate about signal processing and machine learning in a way that my creative mind wishes them to,
		by programming them mostly in Matlab and Python. Currently I am working as <strong> Assistant Professor </strong> in <a href="https://www.cuet.ac.bd/dept/ete"><u><strong>Electronics
		and Telecommunication Engineering </strong> </u></a> department at <a href="https://www.cuet.ac.bd/"><u><strong>Chittagong University of Engineering &amp; Technology</strong> </u></a>, Bangladesh. My
		research interest span the areas of digital speech processing, machine learning, speech enhancement, bandwidth expansion, analysis and modeling of speech and speaker traits, cochlear implants and
		hearing aids and feature estimation in noise, robust speech recognition with current emphasis on robust recognition and training methods for spoken document retrieval and recognition in hands-free
		environments for human-computer interaction.</p>

		<p> I served as a Graduate Research Assistant at Auditory Neuroscience Laboratory in the <a href="https://www.um.edu.my.edu/"><u><strong>University of Malaya, Malaysia</strong> </u></a> from 2013 to 2015,
		funded by <a href="https://www.um.edu.my/faq-research-high-impact-research-grant"><u><strong>High Impact Research project</strong> </u></a> and <a href="https://umresearch.um.edu.my/funding-opportunities-information"><u><strong>University
		Malaya Research Grant</strong> </u></a>. I received my MS degree in    <a href="https://engine.um.edu.my/about-biomedical-engineering"><u><strong>Biomedical Engineering</strong> </u></a> with hearing and signal processing
		concentration from University of Malaya, Malaysia in 2015 and received <strong> DISTICTION </strong> for outstanding research. </p>

		<p>I am currently open for <strong>internships</strong> and co-ops to expand my umbrella of technical expertise in the field of Speech and Signal Processing and Machine Learning with a aim of serving technology with my creative vision and ideas.</p>


		<p class="links"><a href="#"></a></p>

	</div>
</div>
        </template>
        <template #sidebar>
<ul>
	<li>

		<div id="search">
		<br>
		<br>
		<br>
			<p class="links"><a :href="$url('/2020-Nursadul-Mamun-Resume-Internship-2020.pdf')" class="button">Download My Resume</a></p>
			<br>
			<p class="links"><a href="mailto:nxm175730@utdallas.edu" class="button"> Send me an Email</a></p>
			<br>
		</div>
		<div style="clear: both;"> </div>
	</li>
	<li>
		<br>
		<br>
		<br>
		<br>
			<br>
			<h2><strong>Skills</strong></h2>
			<h3>Programming Language:</h3>
			-MATLAB <br>
			-Python <br>
			-C/C++ <br>
		<br>
		<h3> Machine Learning </h3>
		-Machine Learning Libraries:<br>
		      ~ Scikit-Learn<br>
		      ~ SciPy<br>
		      ~ Numpy<br>
		      ~ Pandas<br>
		      ~ TensorFlow<br>
		      ~ Keras<br>

		-Machine Learning Tools:<br>
		      ~ Classification<br>
		      ~ Regression<br>
		      ~ Clustering<br>
		      ~ PCA, LDA, SVD<br>

		-Deep Learning Tools:<br>
		      ~ DNN<br>
		      ~ CNN<br>
		      ~ RNN/LSTM<br>

		<br>
		<h3> Circuit Simulation</h3>
		- P-Spice<br>
		- DSCH<br>
		- Microwind &amp; CADENCE

		<br><br>
		<h2><strong>Acheivements</strong></h2>
		-<a href="https://www.nibib.nih.gov/research-funding/nih-research-project-grant-program-r01"> NIH Research Project Grant(R01)(2018~present)</a>
		<br>
		- <a href="https://acousticalsociety.org/176th-meeting-acoustical-society-of-america/">ASA Student Travel Award</a>
		<br>
		- <a href="https://www.um.edu.my/high-impact-research//">HIR Research Project Grant (2014~2015)</a>
		<br>
		- <a href="https://www.um.edu.my/research-and-community/information-for-researchers/research-clusters/wellness-cluster/research-projects/umrg-programme//">UMRG Research Project Grant (2013)</a>
	</li>

</ul>
        </template>
    </SiteLayout>
</template>
