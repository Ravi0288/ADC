from django.urls import path, include
from rest_framework.routers import DefaultRouter
from .source_json_for_db import SyncFromSourceView, URL_to_be_accessed_view, download_and_read_source_json, Download_history_view
from .research_document import download_research_documents, research_docs_view
from django.conf import settings
from django.conf.urls.static import static
from .read_source_json_and_write_in_file import read_json_and_write_in_file

router = DefaultRouter()
# router.register('source-json', SyncFromSourceView)
# router.register('url-to-be-accessed', URL_to_be_accessed_view)
# router.register('downloaded-documents', research_docs_view)
# router.register('downloaded-history', Download_history_view)

urlpatterns = [
    path('', include(router.urls)),
    # path('download-and-read-source-json/', download_and_read_source_json),
    path('download-and-read-source-json/', read_json_and_write_in_file),
    # path('download-research-docs/', download_research_documents),
]


