<?php

namespace App\Controller;

use App\Service\LeadService;
use App\Service\ApiLogService;
use App\DTO\CreateLeadDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use App\Exception\ValidationException;
use App\Exception\DuplicateLeadException;

#[Route('/api')]
class LeadController extends AbstractController
{
    public function __construct(
        private LeadService $leadService,
        private ApiLogService $apiLogService,
        private EncoderInterface $jsonEncoder
    ) {}

    #[Route('/leads', name: 'create_lead', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] CreateLeadDTO $dto,
        Request $request
    ): JsonResponse {
        try {
            $lead = $this->leadService->createLead($dto);

            $responseData = [
                'status' => 'success',
                'code' => Response::HTTP_CREATED,
                'message' => 'Lead created successfully',
                'data' => $this->jsonEncoder->encode($lead, 'json')
            ];

            $this->apiLogService->log($request, $responseData, Response::HTTP_CREATED);
            return $this->json($responseData, Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            $responseData = [
                'status' => 'error',
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Validation failed',
                'errors' => $this->jsonEncoder->encode($e->getViolations(), 'json')
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_BAD_REQUEST);
            return $this->json($responseData, Response::HTTP_BAD_REQUEST);

        } catch (DuplicateLeadException $e) {
            $responseData = [
                'status' => 'error',
                'code' => Response::HTTP_CONFLICT,
                'message' => 'Duplicate lead detected',
                'errors' => ['email' => 'A lead with this email already exists']
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_CONFLICT);
            return $this->json($responseData, Response::HTTP_CONFLICT);

        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An error occurred while processing the request'
            ];

            if ($this->getParameter('kernel.environment') === 'dev') {
                $responseData['debug'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
            }

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

            $responseData = [
                'status' => 'success',
                'code' => Response::HTTP_OK,
                'message' => 'Leads retrieved successfully',
                'data' => [
                    'items' => $this->jsonEncoder->encode($result['data'], 'json'),
                    'pagination' => $result['pagination']
                ]
            ];

            $this->apiLogService->log($request, $responseData, Response::HTTP_OK);
            return $this->json($responseData, Response::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            $responseData = [
                'status' => 'error',
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => $e->getMessage()
            ];
            $this->apiLogService->log($request, $responseData, Response::HTTP_BAD_REQUEST);
            return $this->json($responseData, Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            $responseData = [
                'status' => 'error',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'An error occurred while fetching leads'
            ];

            if ($this->getParameter('kernel.environment') === 'dev') {
                $responseData['debug'] = [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
            }

            $this->apiLogService->log($request, $responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
            return $this->json($responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
