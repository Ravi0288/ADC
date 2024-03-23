'''
########################################## INTRODUCTION TO THE PAGE ############################################
This page provides Research_document, and it's serializer and view.
1 Research_document : URLS from URLs_to_be_downloaded model will be accessed and the downloade file will be kept in this model.
    and the accessed satatus will be update in URL_to_be_accessed model as last access status.

 sequence of actions:
    localhost:8000/api/download-research-docs => download the file, and store in Research_document model.

    these files can be accessed from the link localhost:8000/api/documents
'''


from django.db import models
from django.core.files.storage import FileSystemStorage
import os
from django.dispatch import receiver
import html2text
from rest_framework.viewsets import ModelViewSet
from rest_framework.serializers import ModelSerializer
from datetime import datetime
from django.db.models import Q
from rest_framework.decorators import api_view
import requests
from rest_framework.response import Response
from django.core.files.base import ContentFile
import requests
import os
from .source_json import URL_to_be_accessed
import urllib.parse
from django.db.models.signals import pre_save


'''
Class to remove the existing file.
This class will ensure to overwrite the existing file in case of same file is update in any recor
'''
class Over_write_storage(FileSystemStorage):
    def get_replace_or_create_file(self, name, max_length=None):
        print(self.location, "######################")
        if self.exists(name):
            os.remove(os.path.join(self.location, name))
            return super(Over_write_storage, self).get_replace_or_create_file(name, max_length)


'''
    Function to return the storage file path as string.
    This function will return file path as article_library/file_name_with_extension
    Any downloaded file will be stored at this path.
'''      
def get_file_path(instance, filename):
    return '{0}'.format(
        filename
        )


# Model to record logs of downloaded files/folders from API's
class Research_document(models.Model):
    source = models.ForeignKey(URL_to_be_accessed, on_delete=models.CASCADE, related_name="documents")
    source_name = models.CharField(max_length=30)
    file_content = models.FileField(upload_to=get_file_path, blank=True, null=True, storage=Over_write_storage)
    file_name = models.CharField(max_length=500)
    file_size = models.BigIntegerField(default=0)
    file_type = models.CharField(max_length=20)
    received_on = models.DateTimeField(auto_now_add=True)
    processed_on = models.DateTimeField(null=True)
    status = models.CharField(max_length=12)
    bureau_code = models.CharField(max_length=20)


    def __str__(self) -> str:
        return self.source
    

# serializer for SyncFromSource model
class Research_document_serializer(ModelSerializer):
    class Meta:
        model = Research_document
        fields = '__all__'


# views for SyncFromSource
class research_docs_view(ModelViewSet):
    queryset = Research_document.objects.all()
    serializer_class = Research_document_serializer



# function to download file from the queried links
@api_view(['GET'])
def download_research_documents(request):

    # to filter specific bureau_code provide bureau_code in query params
    bureau_code = request.GET.get('bureau_code')
    '''
    if bureau_code as query params received filter the relevant queries
     that have bureau_code and either being accessed first time or last status was failed or due for download today.
     'initial' is for first time accessed. 'Failed' shows the last time it was accessed but operation  was failed.
     'next_due_date' is the due date to be accessed whether last status of access was failed or success.
    '''
    if bureau_code:
        urls = URL_to_be_accessed.objects.filter(
            Q(last_accessed_status__in = ('failed', 'initial')) |
            Q(next_due_date__lte = datetime.today()
            ).filter(bureau_code=bureau_code)
        ) 

    # bureau_code not provide, filter all the query that are being accessed first time or last status was failed or due for download today
    else:
        urls = URL_to_be_accessed.objects.filter(
            Q(last_accessed_status__in = ('failed', 'initial')) |
            Q(next_due_date__lte = datetime.today())
            )

    urls = URL_to_be_accessed.objects.all()

    # iterate through the urls in query
    for item in urls:
        try:
            response = requests.get(item.download_URL, verify=False)
            if response.status_code == 200:
                # Retrieve file name and file size from response headers
                content_disposition = response.headers.get('content-disposition')
                if content_disposition:
                    file_name = content_disposition.split('filename=')[1]
                else:
                    file_name = item.download_URL.split('/')[-1]  # Use URL as filename if content-disposition is not provided
                # decode the url to get the exact file name
                file_name = urllib.parse.unquote(file_name)

                file_size = int(response.headers.get('content-length', 0))
                file_type = os.path.splitext(file_name)[1]

                # query to check if the same record exists
                qs = Research_document.objects.filter(file_name=file_name)

                # if record exists and the size is also same, dont do anything
                if qs.exists() and qs[0].file_size == file_size:
                    pass
                    # continue

                # if record exists but the size is different, update the file
                elif qs.exists() and not qs[0].file_size == file_size:
                    qs[0].file_size = file_size
                    qs[0].file_content.save(file_name, ContentFile(response.content))
                    # continue               
                    
                # if record not found create new record
                else:
                    x = Research_document.objects.create(
                        file_name = file_name,
                        source = item,
                        processed_on = datetime.today(),
                        status = 'success',
                        file_size = file_size,
                        file_type = file_type
                    )
                    # save file
                    x.file_content.save(file_name, ContentFile(response.content))

                # finally update the last accessed success status
                item.last_accessed_status = 'success'
                item.save()
            else:
                item.last_accessed_status = 'failed'
                item.last_error_message = html2text.html2text(response.text)
                item.save()                

        except Exception as e:
            # update the failed status
            item.last_accessed_status = 'failed'
            item.last_error_message = e
            item.save()

    return Response("successfully executed")



#It will activate whenever you will save file in uploadfolder model
@receiver(pre_save, sender=Research_document)
def file_update(sender, **kwargs):
    upload_folder_instance = kwargs['instance']
    if upload_folder_instance.id:
        path = 'media_library/' + upload_folder_instance.file_name
        print(path, "$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$4")
        os.remove(path)