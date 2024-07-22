from django.urls import path
from .download import download_xml_file
# seperate_record_by_assesion_number

urlpatterns = [
    path('download/', download_xml_file),
    # path('process-file/', seperate_record_by_assesion_number),
]
