from django.urls import path, include
from rest_framework.routers import DefaultRouter
# from .source_json_for_db import SyncFromSourceView, URL_to_be_accessed_view, download_and_read_source_json, Download_history_view
# from .research_document import download_research_documents, research_docs_view
from .datagov.data_gov_cache import read_json_and_write_in_file
from .datagov.data_gov_migrate import push_to_figshare
from .views import report_view_index, ERS, FSA, FAS, FSLM, NRCS


router = DefaultRouter()
# router.register('source-json', SyncFromSourceView)
# router.register('url-to-be-accessed', URL_to_be_accessed_view)
# router.register('downloaded-documents', research_docs_view)
# router.register('downloaded-history', Download_history_view)

urlpatterns = [
    path('drf_views', include(router.urls)),
    # path('download-research-docs/', download_research_documents),
    # path('download-and-read-source-json/', download_and_read_source_json),
    path('datagov/download-and-read-source-json/', read_json_and_write_in_file),
    path('datagov/push-to-figshare/', push_to_figshare),

    # ui endpoints
    path('reports/', report_view_index, name='reports'),
    path('ers/', ERS, name='ers'),
    path('fsa/', FSA, name='fsa'),
    path('fas/', FAS, name='fas'),
    path('fslm/', FSLM, name='fslm'),
    path('nrcs/', NRCS, name='nrcs'),

]


