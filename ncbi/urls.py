from django.urls import path
from .download import download_xml_file

urlpatterns = [
    path('download/', download_xml_file),
]
