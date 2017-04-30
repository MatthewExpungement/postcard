

### Tables


Tables_in_wwwclues_civil_district
case_history
case_information
complaint_information
hearings_set
judgment
motions
related_person
scheduled_events
service



### Queries

Get the total number of cases for each location
```
SELECT Zip, COUNT(*) AS ZipCount 
FROM related_person 
INNER JOIN case_information ON 
case_information.CaseNumber = related_person.CaseNumber
WHERE CaseLocation = '0101' AND YEAR(Filing_Date_Readable) = '2016' 
GROUP BY Zip 
ORDER BY ZipCount DESC
```

Get the percentage of default cases for each process server
```
SELECT Name, cases, defaults, 
		FORMAT(ROUND((defaults*100.0)/cases,2),"%0.00") as PercentDefault, dismissal3507,
		FORMAT(ROUND((dismissal3507*100.0)/cases,2),"%0.00") as PercentDism
FROM (
	SELECT Name,
	count(casenumber) as cases,
	sum(CASE WHEN JudgmentType= 'AFFIDAVIT JUDGMENT ENTERED' OR JudgmentType='DEFAULT JUDGMENT ENTERED' THEN 1 ELSE 0 END) as defaults,
	sum(CASE WHEN JudgmentType='COMPLAINT DISMISSED (RULE 3-507)' THEN 1 ELSE 0 END) as dismissal3507
	FROM (
		SELECT Name, CaseNumber, ComplaintNumber
		FROM related_person
		WHERE connection='PRIVATE PROCESS SERVER'
	) as t1

	INNER JOIN (
		SELECT CaseNumber,ComplaintNumber,JudgmentType  
		FROM judgment) as t2
	USING (CaseNumber,ComplaintNumber)
	GROUP BY Name ORDER BY defaults DESC LIMIT 100
) as t2
GROUP BY Name ORDER BY PercentDefault DESC
```




Table of all cases with process server name and the respective defendant's ZIP code. 


```
SELECT DISTINCT defendants.CaseNumber, Zip
FROM 
	(SELECT CaseNumber, Zip
	FROM related_person
	WHERE Connection = 'DEFENDANT') AS defendants
	INNER JOIN (SELECT CaseNumber, 
				Name AS ProcessServName
		 FROM related_person
		 WHERE Connection = 'PRIVATE PROCESS SERVER') AS processServs
	ON defendants.CaseNumber = processServs.CaseNumber

```




MAIN QUERY: 
Get percentage defaults for each process server, across all their zips they served

SELECT 
		ProcessServName,
		DefendantZip,
		count(*) AS totalNumCases,
		sum(CASE WHEN JudgmentType='DEFAULT JUDGMENT ENTERED' THEN 1 ELSE 0 END) as numDefaults
FROM

	(SELECT CaseNumber
	 FROM 	case_information
	 WHERE 	CaseLocation = '0101' AND YEAR(Filing_Date_Readable) = '2016' AND ClaimType='CONTRACT'
	) AS bmoreCases

JOIN
	(SELECT DISTINCT CaseNumber, 
			Zip AS DefendantZip
	FROM related_person
	WHERE Connection = 'DEFENDANT') defendants
ON  defendants.CaseNumber = bmoreCases.CaseNumber

JOIN 
	(SELECT CaseNumber, 
			Name AS ProcessServName
	 FROM related_person
	 WHERE Connection = 'PRIVATE PROCESS SERVER'
	) processServs
ON defendants.CaseNumber = processServs.CaseNumber

JOIN judgment 
ON judgment.CaseNumber = processServs.CaseNumber 	

GROUP BY ProcessServName, DefendantZip


Similar query as the main one above, BUT we consider either judgment of 'DEFAULT JUDGMENT ENTERED' OR 'AFFIDAVIT JUDGMENT ENTERED'
as "defaulted" outcomes. Also, we remove cases with 'COMPLAINT DISMISSED (RULE 3-507)' in the total count of cases.

ALSO, limit data to year 2016, for just contract cases, and located in baltimore

SELECT 
		ProcessServName,
		DefendantZip,
		sum(CASE WHEN JudgmentType<>'COMPLAINT DISMISSED (RULE 3-507)' THEN 1 ELSE 0 END) as totalNumCases,
		sum(CASE WHEN JudgmentType='DEFAULT JUDGMENT ENTERED' OR JudgmentType='AFFIDAVIT JUDGMENT ENTERED' THEN 1 ELSE 0 END) as numDefaults
FROM
	(SELECT CaseNumber
	 FROM 	case_information
	 WHERE 	CaseLocation = '0101' AND YEAR(Filing_Date_Readable) = '2016' AND ClaimType='CONTRACT'
	) AS bmoreCases

JOIN
	(SELECT DISTINCT CaseNumber, 
			Zip AS DefendantZip
	FROM related_person
	WHERE Connection = 'DEFENDANT') defendants
ON  defendants.CaseNumber = bmoreCases.CaseNumber
	
JOIN 
	(SELECT CaseNumber, 
			Name AS ProcessServName
	 FROM related_person
	 WHERE Connection = 'PRIVATE PROCESS SERVER'
	) processServs
ON defendants.CaseNumber = processServs.CaseNumber

JOIN judgment 
ON judgment.CaseNumber = processServs.CaseNumber 	

GROUP BY ProcessServName, DefendantZip



Query to get, for each process server and zip code, the total number of cases not served


SELECT  ProcessServName,
		DefendantZip,
		count(*) AS totalNumCases,
		sum(CASE WHEN Outcome='SV' THEN 1 ELSE 0 END) as numSV,
		sum(CASE WHEN Outcome='RU' THEN 1 ELSE 0 END) as numRU,
		sum(CASE WHEN Outcome='NE' THEN 1 ELSE 0 END) as numNE,
		sum(CASE WHEN Outcome='PS' THEN 1 ELSE 0 END) as numPS,
		sum(CASE WHEN Outcome='MV' THEN 1 ELSE 0 END) as numMV,
		sum(CASE WHEN Outcome='RU' THEN 1 ELSE 0 END) as numRU
FROM
		(SELECT CaseNumber
		 FROM 	case_information
		 WHERE 	CaseLocation = '0101' AND YEAR(Filing_Date_Readable) = '2016' 
		) AS bmoreCases

JOIN	(SELECT DISTINCT CaseNumber, 
				Zip AS DefendantZip
		FROM related_person
		WHERE Connection = 'DEFENDANT') AS defendants
ON 		bmoreCases.CaseNumber = defendants.CaseNumber

JOIN 	(SELECT CaseNumber, 
				Name AS ProcessServName
		 FROM related_person
		 WHERE Connection = 'PRIVATE PROCESS SERVER'
		) AS processServs
ON 		defendants.CaseNumber = processServs.CaseNumber

JOIN	service 
ON 		service.CaseNumber = processServs.CaseNumber

JOIN	judgment
ON 		judgment.CaseNumber = service.CaseNumber

GROUP BY ProcessServName, DefendantZip





Just for Fact checking: Query to get the number of total cases and default cases for Alleyne Emerson in 21206

SELECT defendants.CaseNumber,
			DefendantZip,
			ProcessServName,
			JudgmentType,
			count(*) AS totalNumCases,
			sum(CASE WHEN JudgmentType='DEFAULT JUDGMENT ENTERED' THEN 1 ELSE 0 END) as defCounts
FROM
	(SELECT DISTINCT CaseNumber, 
			Zip AS DefendantZip
	FROM related_person
	WHERE Connection = 'DEFENDANT' AND Zip = '21206') defendants
	
JOIN 
	(SELECT CaseNumber, 
			Name AS ProcessServName
	 FROM related_person
	 WHERE Connection = 'PRIVATE PROCESS SERVER' AND Name = 'ALLEYNE, EMERSON'
	) processServs
	
ON defendants.CaseNumber = processServs.CaseNumber

JOIN judgment 

ON judgment.CaseNumber = processServs.CaseNumber 	



