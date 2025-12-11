import apiClient from './client';

// Types
export type FormulaType = 'earning' | 'deduction' | 'bonus';
export type FormulaCalculationType = 'fixed' | 'percentage' | 'conditional' | 'custom';

export interface FormulaCondition {
  field: string;
  operator: '>=' | '>' | '<=' | '<' | '==' | '!=';
  value: number;
  result: number;
  is_percentage?: boolean;
}

export interface PayrollFormula {
  id: string;
  name: string;
  code: string;
  type: FormulaType;
  type_label: string;
  formula_type: FormulaCalculationType;
  formula_type_label: string;
  formula_expression?: string | null;
  formula_display: string;
  base_field?: string | null;
  base_field_label?: string | null;
  default_amount: number;
  percentage_rate?: number | null;
  is_taxable: boolean;
  is_active: boolean;
  priority: number;
  category: string;
  description?: string | null;
  conditions?: FormulaCondition[] | null;
  created_at?: string;
  updated_at?: string;
}

export interface PayrollFormulaInput {
  name: string;
  code: string;
  type: FormulaType;
  formula_type: FormulaCalculationType;
  formula_expression?: string | null;
  base_field?: string | null;
  default_amount?: number;
  percentage_rate?: number | null;
  is_taxable?: boolean;
  is_active?: boolean;
  priority?: number;
  category?: string;
  description?: string | null;
  conditions?: FormulaCondition[] | null;
}

export interface FormulasResponse {
  formulas: PayrollFormula[];
  meta: {
    total: number;
    types: Record<string, string>;
    formula_types: Record<string, string>;
    base_fields: Record<string, string>;
  };
}

export interface FormulaConfig {
  types: Record<string, string>;
  formula_types: Record<string, string>;
  base_fields: Record<string, string>;
  categories: {
    earning: { value: string; label: string }[];
    deduction: { value: string; label: string }[];
    bonus: { value: string; label: string }[];
  };
  operators: { value: string; label: string }[];
}

export interface FormulaPreviewContext {
  base_salary?: number;
  gross_salary?: number;
  net_salary?: number;
  worked_days?: number;
  worked_hours?: number;
  overtime_hours?: number;
  attendance_rate?: number;
}

export interface FormulaPreviewResult {
  formula: PayrollFormula;
  context: FormulaPreviewContext;
  result: number;
  formatted_result: string;
}

// API Functions
export async function getPayrollFormulas(filters?: {
  type?: FormulaType;
  is_active?: boolean;
  search?: string;
}): Promise<FormulasResponse> {
  const response = await apiClient.get<{ data: FormulasResponse }>('/payroll/formulas', {
    params: filters,
  });
  return response.data.data;
}

export async function getPayrollFormula(id: string): Promise<PayrollFormula> {
  const response = await apiClient.get<{ data: PayrollFormula }>(`/payroll/formulas/${id}`);
  return response.data.data;
}

export async function createPayrollFormula(data: PayrollFormulaInput): Promise<PayrollFormula> {
  const response = await apiClient.post<{ data: PayrollFormula }>('/payroll/formulas', data);
  return response.data.data;
}

export async function updatePayrollFormula(
  id: string,
  data: Partial<PayrollFormulaInput>
): Promise<PayrollFormula> {
  const response = await apiClient.put<{ data: PayrollFormula }>(`/payroll/formulas/${id}`, data);
  return response.data.data;
}

export async function deletePayrollFormula(id: string): Promise<void> {
  await apiClient.delete(`/payroll/formulas/${id}`);
}

export async function toggleFormulaStatus(id: string): Promise<{ id: string; is_active: boolean }> {
  const response = await apiClient.post<{ data: { id: string; is_active: boolean } }>(
    `/payroll/formulas/${id}/toggle-status`
  );
  return response.data.data;
}

export async function previewFormula(
  id: string,
  context?: FormulaPreviewContext
): Promise<FormulaPreviewResult> {
  const response = await apiClient.post<{ data: FormulaPreviewResult }>(
    `/payroll/formulas/${id}/preview`,
    context
  );
  return response.data.data;
}

export async function getFormulaConfig(): Promise<FormulaConfig> {
  const response = await apiClient.get<{ data: FormulaConfig }>('/payroll/formulas/config');
  return response.data.data;
}
