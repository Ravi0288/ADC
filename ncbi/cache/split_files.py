import xml.etree.ElementTree as ET
import os.path
import filecmp
my_path = os.path.abspath(os.path.dirname(__file__))
# f=my_path+'/cypress/downloads/bioproject_result.xml'
f='cypress/downloads/bioproject_result.xml'
newFile=None
identifiers=[]
with open (f, encoding="utf8") as myfile:
    Lines=myfile.readlines() 
    for line in Lines:
        newF=''
        if(line.startswith('<DocumentSummary')):
            start = line.find("uid=") + len("uid=")+1
            end = len(line)-3
            substring = line[start:end]
            identifiers.append(substring)
            if newFile is not None:
                newFile.close()    
            newF=r'../../file_cache/ncbi/'+substring+'_new'
            newFile = open(newF, "w", encoding="utf-8")
            newFile.write(line)
        else:
            newFile.write(line)
newFile.close()
for identifier in identifiers:
    oldF=r'../../file_cache/ncbi/'+identifier
    newF=r'../../file_cache/ncbi/'+identifier+'_new'
    if os.path.isfile(oldF):
        if filecmp.cmp(oldF, newF):
            os.remove(newF)
        else:
            os.remove(oldF)
            os. rename(newF,oldF)
    else:
        os. rename(newF,oldF)

