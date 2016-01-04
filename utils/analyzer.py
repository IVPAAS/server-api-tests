__author__ = 'noam.arad'
import sys
import os
import re


def main(compatCheckDirectory):
 print "starting to analyze dir ["+compatCheckDirectory+"]"
 # compatCheckDirectory = r"\\?"+compatCheckDirectory
 if not os.path.isdir(compatCheckDirectory):
     print "input given is not a valid directory"
     return ""

 result = "" 
 for (dirpath, dirname, filenames) in os.walk(compatCheckDirectory):
  for filename in filenames:
   result += analyzeSingleFile(os.path.join(dirpath, filename))
 return result


def analyzeSingleFile(filename):
 print "current file ["+filename+"]"
 currentFile = open(filename,"r")
 compiledRegEx = re.compile("Error:\s*\((.*)\).*new=(.*)old=(.*)\)")
 # compiledRegEx = re.compile("Error:(.*)")
 # currLineIndex = 0
 lines=currentFile.readlines()
 # for line in currentFile:
 for currLineIndex in range(len(lines)):
  line = lines[currLineIndex].strip()
  # print "line ["+line+"]"
  if line.startswith('Error:'):
   # print "line ["+line+"]"   
   print lines[currLineIndex-2].strip()
   matches = compiledRegEx.match(line)
   # print "found ["+str(len(matches.groups()))+"] matches ["+str(matches.groups())+"]"
   newRes = matches.group(2).split(';')
   oldRes = matches.group(3).split(';')
   inNewNotOld = [val for val in newRes if val.strip()!='' and val not in oldRes]
   if (len(inNewNotOld) > 0):
    print "These are in new but not old ["+str(inNewNotOld)+"]"
   inOldNotNew = [val for val in oldRes if val.strip()!='' and val not in newRes]   
   if (len(inOldNotNew) > 0): 
    print "These are in old but not new ["+str(inOldNotNew)+"]"  
    
   currLineIndex += currLineIndex
   
 currentFile.close() 
 return ""





if __name__ == '__main__':
 if (len(sys.argv) != 2):
  print "USAGE: python analyzer.py <CompatCheck folder>"
  exit(0)


 output = main(sys.argv[1])
 print output
 print "finished"

exit(0)
