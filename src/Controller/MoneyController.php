<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class MoneyController extends AbstractController
{
    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);

        $nullableValue = static function (mixed $value): ?string {
            if ($value === null) {
                return null;
            }
            $trimmed = trim((string) $value);
            return $trimmed !== '' ? $trimmed : null;
        };

        $amountKeys = [
            'opening_umat',
            'opening_masjid',
            'opening_total',
            'income_umat',
            'income_masjid',
            'income_total',
            'position_umat',
            'position_masjid',
            'position_total',
            'expense_umat',
            'expense_masjid',
            'expense_total',
            'closing_umat',
            'closing_masjid',
        ];

        $amounts = [];
        foreach ($amountKeys as $key) {
            $amount = $nullableValue($body[$key] ?? null);
            if ($amount !== null && !is_numeric($amount)) {
                $this->flash('error', 'Cash report amounts must be valid numbers.');

                return $this->redirect($response, '/');
            }
            $amounts[$key] = $amount;
        }

        $closingTotal = ($amounts['closing_umat'] === null && $amounts['closing_masjid'] === null)
            ? null
            : number_format((float) ($amounts['closing_umat'] ?? 0) + (float) ($amounts['closing_masjid'] ?? 0), 2, '.', '');

        $params = [
            $nullableValue($body['opening_date'] ?? null),
            $nullableValue($body['period_start'] ?? null),
            null,
            $amounts['opening_umat'],
            $amounts['opening_masjid'],
            $amounts['opening_total'],
            $amounts['income_umat'],
            $amounts['income_masjid'],
            $amounts['income_total'],
            $nullableValue($body['position_date'] ?? null),
            $amounts['position_umat'],
            $amounts['position_masjid'],
            $amounts['position_total'],
            $nullableValue($body['expense_date'] ?? null),
            $amounts['expense_umat'],
            $amounts['expense_masjid'],
            $amounts['expense_total'],
            $nullableValue($body['closing_date'] ?? null),
            $amounts['closing_umat'],
            $amounts['closing_masjid'],
            $closingTotal,
        ];

        try {
            $pdo = $this->pdo();
            $exists = (int) $pdo->query('SELECT COUNT(*) FROM data_money WHERE id = 1')->fetchColumn() > 0;
            if ($exists) {
                $sql = 'UPDATE data_money SET
                    opening_date = ?, period_start = ?, report_date = ?,
                    opening_umat = ?, opening_masjid = ?, opening_total = ?,
                    income_umat = ?, income_masjid = ?, income_total = ?,
                    position_date = ?, position_umat = ?, position_masjid = ?, position_total = ?,
                    expense_date = ?, expense_umat = ?, expense_masjid = ?, expense_total = ?,
                    closing_date = ?, closing_umat = ?, closing_masjid = ?, closing_total = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE id = 1';
            } else {
                $sql = 'INSERT INTO data_money (
                    id, opening_date, period_start, report_date,
                    opening_umat, opening_masjid, opening_total,
                    income_umat, income_masjid, income_total,
                    position_date, position_umat, position_masjid, position_total,
                    expense_date, expense_umat, expense_masjid, expense_total,
                    closing_date, closing_umat, closing_masjid, closing_total,
                    updated_at
                ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)';
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $this->flash('success', 'Laporan Kas Masjid saved successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to save Laporan Kas Masjid.');
        }

        return $this->redirect($response, '/');
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $this->assertActivated($request);
        $query = $request->getQueryParams();
        $id = (int) ($args['id'] ?? $query['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('error', 'Cash record not found.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare('DELETE FROM data_money WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $this->flash('success', 'Cash record removed.');
            } else {
                $this->flash('error', 'Cash record not found.');
            }
        } catch (Throwable) {
            $this->flash('error', 'Unable to remove cash record.');
        }

        return $this->redirect($response, '/');
    }

    public function legacyAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $query = $request->getQueryParams();

        if (($body['action'] ?? '') === 'update') {
            return $this->update($request, $response);
        }

        if (($query['action'] ?? '') === 'delete') {
            return $this->delete($request, $response);
        }

        return $this->redirect($response, '/');
    }
}
