<?php

namespace App\Controller;

use App\Service\LeadService;
use App\Service\ApiLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Exception\ValidationException;

#[Route('/api')]
class LeadController extends AbstractController
{
    public function __construct(
        private LeadService $leadService,
        private ApiLogService $apiLogService
    ) {
    }

    #[Route('/leads', name: 'create_lead', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON format');
            }

            $lead = $this->leadService->createLead($data);
            
            $responseData = ['id' => $lead->getId()];
            $this->apiLogService->log($request, $responseData, Response::HTTP_CREATED);
            
            return $this->json($responseData, Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            $responseData = [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->getViolations()
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_BAD_REQUEST);
            return $this->json($responseData, Response::HTTP_BAD_REQUEST);

        } catch (\InvalidArgumentException $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_BAD_REQUEST);
            return $this->json($responseData, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => 'An error occurred while processing the request'
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
            return $this->json($responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/leads', name: 'api_leads_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 10);
            $sortBy = $request->query->get('sortBy', 'createdAt');
            $sortOrder = strtoupper($request->query->get('sortOrder', 'DESC'));

            // Validate sort parameters
            $allowedSortFields = ['createdAt', 'firstName', 'lastName', 'email'];
            $allowedSortOrders = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedSortFields)) {
                throw new \InvalidArgumentException('Invalid sort field');
            }
            if (!in_array($sortOrder, $allowedSortOrders)) {
                throw new \InvalidArgumentException('Invalid sort order');
            }

            $result = $this->leadService->getLeads($page, $limit, $sortBy, $sortOrder);
            $this->apiLogService->log($request, $result, Response::HTTP_OK);
            
            return $this->json($result);

        } catch (\InvalidArgumentException $e) {
            $responseData = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_BAD_REQUEST);
            return $this->json($responseData, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'message' => 'An error occurred while fetching leads'
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
            return $this->json($responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 