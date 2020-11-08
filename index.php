<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>BABS ticketing system</title>
  </head>
  <body>
    <div class="container">
      <h1>BABS Ticketing</h1>

      <form action="process.php" method="post">
	
	<div class="form-group row">
	  <label for="scientist"  class="col-sm-2 form-label">Your email address</label>
	  <input type="email" class="form-control col-sm-6" name="scientist" id="scientist" aria-describedby="emailHelp" placeholder="first.second@crick.ac.uk">
	  <small id="emailHelp" class="form-text text-muted col-sm-12">The email address of the person we will primarily dealing with.</small>
	</div>

	<div class="form-group row">
	  <label for="lab" class="col-sm-2 form-label">Your Lab</label>
	  <select class="form-control col-sm-3" id="lab" name="lab" required>
	    <optgroup label="Research Groups" id="lablist">
	      <option disabled selected>Select a Lab/STP.</option>
	    </optgroup>
	    <optgroup label="STPs" id="stplist">
	    </optgroup>
	    <optgroup label="Operations" id="opslist">
	    </optgroup>
	  </select>
	</div>

	<div class="form-group row">
	  <label for="title" class="col-sm-2 form-label">Short Title</label>
	  <input type="text" class="form-control col-sm-6" id="title" aria-describedby="titlelHelp" placeholder="Short descriptive title" name="title" minlength="5" maxlength="50" required>
	  <small id="titleHelp" class="form-text text-muted col-sm-12">The shortest amount of text that will help you, your PI, the bioinformatician recognise the project. Good examples would be 'Power calc for MRC application' or 'RNASeq of CD4+ cells'; bad examples are 'Stats question' or 'CD4 cells'</small>
	</div>

	<div class="form-group row">
	  <label for="code" class="col-sm-2 form-label">Cost Code</label>
	  <input type="text" pattern="[0-9]{5}" class="form-control col-sm-3" id="code" aria-describedby="codeHelp" name="code" required>
	  <small id="codeHelp" class="form-text text-muted col-sm-7">Your PI should be able to provide a code so that STPs can keep track of where their time is spent.</small>
	</div>

	<div class="form-group row">
	  <label for="time" class="col-sm-2 form-label">Estimate of hours</label>
	  <input type="text" pattern="[0-9]+" class="form-control col-sm-3" id="time" aria-describedby="timeHelp"  name="time" required>
	  <small id="timeHelp" class="form-text text-muted col-sm-7">A very rough estimate.  If it's just to book in for a brief initial chat, put 1.</small>
	</div>

	<div class="form-group row">
	  <label for="projtype" class="col-sm-2 form-label">Project Type</label>
	  <select class="form-control col-sm-3" id="projtype" name="projtype" required>
	  </select>
	  <small id="timeHelp" class="form-text text-muted col-sm-7">Type of project</small>
	</div>

	<div class="form-group row">
	  <label for="bioinformatician" class="col-sm-2 form-label">Bioinformatician</label>
	  <select class="form-control col-sm-3" id="bioinformatician" name="bioinformatician" required>
	  </select>
	</div>
	<button type="submit" class="btn btn-primary">Submit</button>
      </form>
      <div class="mt-5 p-2 border">
	<p>Finance requires BABS to record the number of hours we work
	  on projects, andthis will be charged against the code at the
	  current rate of £75/hour. Finance's cost-model doesn't allow
	  us to charge other STPs, or work we do for the Crick or larger
	  scientific community as a whole, development or training etc,
	  so please don't think we earn this!<p>
	  <p>By submitting this form, you are confirming that your PI has agreed the cost-code can be used for this purpose.</p>
	  <p>This charge does not affect the <a href="https://intranet.crick.ac.uk/our-crick/research-integrity/pages/publication-authorship">Crick's
	    authorship policy</a>: regardless of whether it is Core or
	    Grant funded, we generally expect our significant
	    contribution to be recognised in papers - if this needs
	    discussion, please involve us straight away.</p>
      </div>
    </div>
    <script>
     let suggested_sci="<?php echo $_GET["sci"]; ?>";
     if (suggested_sci!="") {
	 document.getElementById('scientist').value = suggested_sci;
	 document.getElementById('scientist').readOnly=true;
     }
     let types = document.getElementById('projtype');
     let suggested_type="<?php echo $_GET["type"]; ?>";
     fetch("types.json")  
	 .then(  
	     function(response) {  
		 response.json().then(function(data) {  
		     let option;
		     if (suggested_type!="") {
			 data={[suggested_type] : data[suggested_type]};
		     }
		     for (const [ key, value ] of Object.entries(data)) {
			 option = document.createElement('option');
			 option.text = value.type;
			 option.value = key;
			 types.add(option);
		     }    
		 });  
	     }  
	 );
     let suggested_bioinf="<?php echo $_GET["id"]; ?>";
     let bioinfs = document.getElementById('bioinformatician');
     fetch("babs_staff.json")  
	 .then(  
	     function(response) {  
		 response.json().then(function(data) {  
		     let option;
		     if (suggested_bioinf!="") {
			 data={[suggested_bioinf] : data[suggested_bioinf]};
		     }
		     for (const [ key, value ] of Object.entries(data)) {
			 option = document.createElement('option');
			 option.text = value.first;
			 option.value = key;
			 bioinfs.add(option);
		     }    
		 });  
	     }  
	 );
     let labs = document.getElementById('lablist');
     let stps = document.getElementById('stplist');
     let ops = document.getElementById('opslist');
     let suggested_lab="<?php echo $_GET["lab"]; ?>";
     fetch("groups.json")  
	 .then(  
	     function(response) {  
		 response.json().then(function(data) {  
		     let option;
		     if (suggested_lab!="") {
			 data.labs=data.labs.filter(d => d==suggested_lab)
			 data.stps=data.stps.filter(d => d==suggested_lab)
			 data.ops=data.ops.filter(d => d==suggested_lab)
		     }
		     for (let i = 0; i < data.labs.length; i++) {
			 option = document.createElement('option');
			 d=data.labs[i];
			 option.text = d.slice(0,1).toUpperCase() +d.slice(1,-1) + ", " + d.slice(-1).toUpperCase();
			 option.value = data.labs[i];
			 labs.appendChild(option);
		     }    
		     for (let i = 0; i < data.stps.length; i++) {
			 option = document.createElement('option');
			 option.text = data.stps[i];
			 option.value = data.stps[i];
			 stps.appendChild(option);
		     }    
		     for (let i = 0; i < data.ops.length; i++) {
			 option = document.createElement('option');
			 option.text = data.ops[i];
			 option.value = data.ops[i];
			 ops.appendChild(option);
		     }    
		 });  
	     }  
	 );
    </script>
  </body>
</html>
