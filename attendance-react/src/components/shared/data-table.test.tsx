import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { DataTable, type Column } from './data-table';

interface TestData {
  id: number;
  name: string;
  email: string;
  status: string;
}

const mockData: TestData[] = [
  { id: 1, name: 'Ahmad Rizki', email: 'ahmad@example.com', status: 'active' },
  { id: 2, name: 'Siti Nurhaliza', email: 'siti@example.com', status: 'active' },
  { id: 3, name: 'Budi Santoso', email: 'budi@example.com', status: 'inactive' },
];

const columns: Column<TestData>[] = [
  { key: 'name', header: 'Name' },
  { key: 'email', header: 'Email' },
  { key: 'status', header: 'Status' },
];

describe('DataTable Component', () => {
  describe('Rendering', () => {
    it('should render table with headers', () => {
      render(<DataTable columns={columns} data={mockData} />);

      expect(screen.getByText('Name')).toBeInTheDocument();
      expect(screen.getByText('Email')).toBeInTheDocument();
      expect(screen.getByText('Status')).toBeInTheDocument();
    });

    it('should render data rows', () => {
      render(<DataTable columns={columns} data={mockData} />);

      expect(screen.getByText('Ahmad Rizki')).toBeInTheDocument();
      expect(screen.getByText('ahmad@example.com')).toBeInTheDocument();
      expect(screen.getByText('Siti Nurhaliza')).toBeInTheDocument();
      expect(screen.getByText('Budi Santoso')).toBeInTheDocument();
    });

    it('should render custom cell content', () => {
      const columnsWithCustomCell: Column<TestData>[] = [
        { key: 'name', header: 'Name' },
        {
          key: 'status',
          header: 'Status',
          cell: (row) => <span data-testid={`status-${row.id}`}>{row.status.toUpperCase()}</span>,
        },
      ];

      render(<DataTable columns={columnsWithCustomCell} data={mockData} />);

      expect(screen.getByTestId('status-1')).toHaveTextContent('ACTIVE');
      expect(screen.getByTestId('status-3')).toHaveTextContent('INACTIVE');
    });
  });

  describe('Empty State', () => {
    it('should show default empty message when no data', () => {
      render(<DataTable columns={columns} data={[]} />);

      expect(screen.getByText('Tidak ada data')).toBeInTheDocument();
    });

    it('should show custom empty message', () => {
      render(<DataTable columns={columns} data={[]} emptyMessage="No employees found" />);

      expect(screen.getByText('No employees found')).toBeInTheDocument();
    });
  });

  describe('Loading State', () => {
    it('should show loading indicator when isLoading is true', () => {
      render(<DataTable columns={columns} data={[]} isLoading={true} />);

      expect(screen.getByText('Memuat data...')).toBeInTheDocument();
    });

    it('should not show data when loading', () => {
      render(<DataTable columns={columns} data={mockData} isLoading={true} />);

      expect(screen.queryByText('Ahmad Rizki')).not.toBeInTheDocument();
    });
  });

  describe('Search', () => {
    it('should render search input when onSearchChange is provided', () => {
      const onSearchChange = vi.fn();
      render(
        <DataTable
          columns={columns}
          data={mockData}
          searchValue=""
          onSearchChange={onSearchChange}
        />
      );

      expect(screen.getByPlaceholderText('Cari...')).toBeInTheDocument();
    });

    it('should call onSearchChange when typing in search', async () => {
      const user = userEvent.setup();
      const onSearchChange = vi.fn();

      // Track the value to simulate controlled behavior
      let currentValue = '';
      onSearchChange.mockImplementation((value: string) => {
        currentValue = value;
      });

      const { rerender } = render(
        <DataTable
          columns={columns}
          data={mockData}
          searchValue={currentValue}
          onSearchChange={onSearchChange}
        />
      );

      const searchInput = screen.getByPlaceholderText('Cari...');

      // Type and rerender to simulate controlled component
      await user.type(searchInput, 'a');
      rerender(
        <DataTable
          columns={columns}
          data={mockData}
          searchValue={currentValue}
          onSearchChange={onSearchChange}
        />
      );

      expect(onSearchChange).toHaveBeenCalled();
      expect(onSearchChange).toHaveBeenCalledWith(expect.stringContaining('a'));
    });

    it('should use custom search placeholder', () => {
      const onSearchChange = vi.fn();
      render(
        <DataTable
          columns={columns}
          data={mockData}
          searchPlaceholder="Search employees..."
          onSearchChange={onSearchChange}
        />
      );

      expect(screen.getByPlaceholderText('Search employees...')).toBeInTheDocument();
    });

    it('should not render search input when onSearchChange is not provided', () => {
      render(<DataTable columns={columns} data={mockData} />);

      expect(screen.queryByPlaceholderText('Cari...')).not.toBeInTheDocument();
    });
  });

  describe('Pagination', () => {
    it('should show pagination info', () => {
      render(
        <DataTable
          columns={columns}
          data={mockData}
          page={1}
          pageSize={10}
          totalPages={3}
          totalItems={25}
          onPageChange={vi.fn()}
        />
      );

      expect(screen.getByText('dari 25 data')).toBeInTheDocument();
      expect(screen.getByText('1 / 3')).toBeInTheDocument();
    });

    it('should call onPageChange when clicking next page', async () => {
      const user = userEvent.setup();
      const onPageChange = vi.fn();

      render(
        <DataTable
          columns={columns}
          data={mockData}
          page={1}
          pageSize={10}
          totalPages={3}
          totalItems={25}
          onPageChange={onPageChange}
        />
      );

      // Find the next page button (ChevronRight)
      const buttons = screen.getAllByRole('button');
      const nextButton = buttons.find(btn => !btn.hasAttribute('disabled') && btn.querySelector('svg'));
      if (nextButton) {
        await user.click(nextButton);
      }

      expect(onPageChange).toHaveBeenCalled();
    });

    it('should disable first page button when on first page', () => {
      render(
        <DataTable
          columns={columns}
          data={mockData}
          page={1}
          pageSize={10}
          totalPages={3}
          totalItems={25}
          onPageChange={vi.fn()}
        />
      );

      const buttons = screen.getAllByRole('button');
      const firstPageButton = buttons[0];
      const prevPageButton = buttons[1];

      expect(firstPageButton).toBeDisabled();
      expect(prevPageButton).toBeDisabled();
    });

    it('should disable last page button when on last page', () => {
      render(
        <DataTable
          columns={columns}
          data={mockData}
          page={3}
          pageSize={10}
          totalPages={3}
          totalItems={25}
          onPageChange={vi.fn()}
        />
      );

      const buttons = screen.getAllByRole('button');
      const lastPageButton = buttons[buttons.length - 1];
      const nextPageButton = buttons[buttons.length - 2];

      expect(lastPageButton).toBeDisabled();
      expect(nextPageButton).toBeDisabled();
    });

    it('should not show pagination buttons when only one page', () => {
      render(
        <DataTable
          columns={columns}
          data={mockData}
          page={1}
          pageSize={10}
          totalPages={1}
          totalItems={3}
          onPageChange={vi.fn()}
        />
      );

      // Should not show page navigation when totalPages is 1
      expect(screen.queryByText('1 / 1')).not.toBeInTheDocument();
    });
  });
});
