# f1-to-pco-giving
Script that can be used to move FellowshipOne giving records (or any CSV really, with some data manipulation) to Planning Center Giving

# Languages
Can be run using PHP 5 or 7 from the command line. I use it on a Mac in a Bash shell, but have been told it also works using the standard command prompt on Windows 10

# Authentication 
The script uses a personal access token, which can be found from your Planning Center Online account (api.planningcenteronline.com). Paste these values into the fields at the top of the script

# Input Files
You will need the script (giving.php), a CSV export of your giving records (donations.csv), and a CSV list of the people in your Planning Center People database, which can be exported from the web interface (people.csv). See the sample headers of donations.csv and people.csv in this repository, and match your CSV to these columns or adjust the script to point to the right columns in your files. 

# Matching Donations to People 
This is the most difficult part to think through. Naturally, PCO and F1 (and any other database provider) will have their own set of IDs for the people in your databse, and they won't match. You could write some code to try and match names or any other values exactly, but there will likely be exceptions or errors with this method, particularly for larger churches (our primary database has about 4,500 people records). 

Instead, what we chose to do is add two fields in a custom tab in PCO - one for FellowshipOne individial ID, and one for their household ID. We imported these values when we first imported our people records to Planning Center People. 

The giving export CSV from FellowshipOne will have a field for contributor ID - this is the individial's ID if the contribution was credited to a specifc person, or the household ID if it was credited to a household. 

Planning Center Giving does not allow for contributions to be credited to a househould (for very valid reasons). So the script first searches the column of individual F1 IDs (from people.csv) for an exact match, and if it finds one - it just posts it to that person's record. If an exact match is not found, it then searches the household ID column and credits the donation to the first match it finds. It is using a simple sequential search, so I would suggest sorting your people.csv file to account for this. We sorted from top to bottom in this order - adult male, adult female, male child, female child.

# Batches 
 The script is posting a month of transactions to each batch. When it reaches the end of that month, it is creating a new batch with the month (January 2016 for example) and then posting the donations to that batch. 

# Running the import 
I have been running our imports a month at a time. The script prints a new line with the request object for each donation it posts, and it prints out an error message followed by the request object that errored out if it encounters an error. I typically redirect the output to a text file for review later, using the command:

php giving.php > this_month.txt

After the script finishes running, I go and manually enter any donations with errors it encountered, verify the totals across both databases, and then commit the batch 






